<?php

declare(strict_types=1);

// Proceso que vacía la bandeja de salida de correos (tabla a_outbox): envía por SMTP
// los mensajes pendientes con reintentos y cada hora purga los que agotaron sus
// intentos. PHP atiende cada petición en un proceso aparte, así que el envío en
// segundo plano corre aquí; en Docker es el servicio "mailer".
// En local: php bin/outbox-worker.php

require __DIR__ . '/../vendor/autoload.php';

use Encuestas\Data\Database;
use Encuestas\Data\EmailOutbox;
use Encuestas\Infrastructure\Logger;
use Encuestas\Web\Services\SmtpEmailSender;

const POLL_SECONDS = 2;
const ERROR_DELAY_SECONDS = 30;
const PURGE_SECONDS = 3600;

$logger = new Logger();
$sender = SmtpEmailSender::fromEnvironment();
$outbox = new EmailOutbox(Database::fromEnvironment());
$logger->info('Despachador de correos iniciado');

$lastPurge = 0;
// Corre hasta que Docker detiene el contenedor.
while (true) { // @phpstan-ignore while.alwaysTrue
    try {
        while ($outbox->dispatchBatch($sender, $logger) > 0) {
            // Sigue mientras haya mensajes listos para enviar.
        }
        if (time() - $lastPurge >= PURGE_SECONDS) {
            $outbox->purgeFailed();
            $lastPurge = time();
        }
        sleep(POLL_SECONDS);
    } catch (Throwable $exception) {
        // P. ej. la base de datos aún no está lista o se reinició: se reintenta más tarde.
        $logger->error('Error al procesar la bandeja de salida: ' . $exception->getMessage());
        sleep(ERROR_DELAY_SECONDS);
        // Conexión nueva: la anterior pudo quedar inservible.
        $outbox = new EmailOutbox(Database::fromEnvironment());
    }
}
