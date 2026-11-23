<?php

declare(strict_types=1);

namespace Encuestas\Data;

use Encuestas\Infrastructure\Logger;
use Encuestas\Web\Services\EmailSender;
use Throwable;

/**
 * Bandeja de salida de correos (tabla a_outbox). Las peticiones solo encolan, así
 * que nunca esperan al SMTP ni fallan si está caído; bin/outbox-worker.php envía en
 * segundo plano con reintentos y espera exponencial. Los mensajes enviados se
 * borran porque llevan enlaces con tokens.
 */
final class EmailOutbox
{
    public const MAX_ATTEMPTS = 8;
    private const BATCH_SIZE = 10;
    private const BASE_DELAY_SECONDS = 30;
    private const MAX_DELAY_SECONDS = 3600;

    public function __construct(private readonly Database $db)
    {
    }

    public function enqueue(string $recipient, string $subject, string $htmlBody): void
    {
        $this->db->connection()->prepare(
            'INSERT INTO a_outbox(o_recipient, o_subject, o_body, o_next_attempt_at) VALUES(?, ?, ?, UTC_TIMESTAMP())'
        )->execute([$recipient, $subject, $htmlBody]);
    }

    /**
     * Envía un lote de mensajes pendientes. Las filas se bloquean con SKIP LOCKED,
     * así que varios procesos pueden despachar a la vez sin repetir un correo.
     *
     * @return int número de mensajes procesados (enviados o reprogramados)
     */
    public function dispatchBatch(EmailSender $sender, Logger $logger): int
    {
        $connection = $this->db->connection();
        $connection->beginTransaction();
        try {
            $statement = $connection->prepare(
                'SELECT o_key, o_recipient, o_subject, o_body, o_attempts FROM a_outbox '
                . 'WHERE o_failed_at IS NULL AND o_next_attempt_at <= UTC_TIMESTAMP() '
                . 'ORDER BY o_key LIMIT ' . self::BATCH_SIZE . ' FOR UPDATE SKIP LOCKED'
            );
            $statement->execute();
            /** @var list<array{o_key: int|string, o_recipient: string, o_subject: string, o_body: string, o_attempts: int|string}> $messages */
            $messages = $statement->fetchAll();

            foreach ($messages as $message) {
                $this->send($sender, $logger, $message);
            }
            $connection->commit();
            return count($messages);
        } catch (Throwable $exception) {
            $connection->rollBack();
            throw $exception;
        }
    }

    /** Borra los mensajes que agotaron sus reintentos hace más de una semana. */
    public function purgeFailed(): void
    {
        $this->db->connection()->exec('DELETE FROM a_outbox WHERE o_failed_at < UTC_TIMESTAMP() - INTERVAL 7 DAY');
    }

    /** Espera exponencial: 30 s, 1 min, 2 min… hasta 1 h. */
    public static function backoffSeconds(int $attempts): int
    {
        return min(self::BASE_DELAY_SECONDS * 2 ** min($attempts - 1, 20), self::MAX_DELAY_SECONDS);
    }

    /**
     * @param array{o_key: int|string, o_recipient: string, o_subject: string, o_body: string, o_attempts: int|string} $message
     */
    private function send(EmailSender $sender, Logger $logger, array $message): void
    {
        $id = (int) $message['o_key'];
        try {
            $sender->send($message['o_recipient'], $message['o_subject'], $message['o_body']);
            $this->db->connection()->prepare('DELETE FROM a_outbox WHERE o_key=?')->execute([$id]);
        } catch (\RuntimeException $exception) {
            $attempts = (int) $message['o_attempts'] + 1;
            $failed = $attempts >= self::MAX_ATTEMPTS;
            // El destinatario no se registra: es un dato personal.
            $logger->warning('Fallo al enviar un correo', [
                'outboxId' => $id,
                'attempt' => $attempts,
                'error' => $exception->getMessage(),
            ]);
            $this->db->connection()->prepare(
                'UPDATE a_outbox SET o_attempts=?, o_last_error=?, '
                . 'o_next_attempt_at=UTC_TIMESTAMP() + INTERVAL ? SECOND, '
                . 'o_failed_at=IF(?, UTC_TIMESTAMP(), NULL) WHERE o_key=?'
            )->execute([
                $attempts,
                mb_substr($exception->getMessage(), 0, 200),
                self::backoffSeconds($attempts),
                $failed ? 1 : 0,
                $id,
            ]);
        }
    }
}
