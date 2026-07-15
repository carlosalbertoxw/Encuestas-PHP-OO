<?php

declare(strict_types=1);

namespace Encuestas\Web\Services;

use Encuestas\Data\Database;

/**
 * Implementación respaldada en MySQL (tabla a_attempt_counters). Cada petición
 * PHP es un proceso nuevo, así que este estado se comparte vía base de datos.
 */
final class DbAttemptCounterStore implements AttemptCounterStore
{
    public function __construct(private readonly Database $db)
    {
    }

    public function count(string $key, int $windowSeconds): int
    {
        $statement = $this->db->connection()->prepare(
            'SELECT ac_count FROM a_attempt_counters WHERE ac_key=? AND ac_window_start > (UTC_TIMESTAMP() - INTERVAL ? SECOND)'
        );
        $statement->execute([$key, $windowSeconds]);
        $count = $statement->fetchColumn();
        return $count === false ? 0 : (int) $count;
    }

    public function increment(string $key, int $windowSeconds): void
    {
        // Purga oportunista de contadores caducados para que la tabla no crezca.
        $this->db->connection()
            ->prepare('DELETE FROM a_attempt_counters WHERE ac_window_start < (UTC_TIMESTAMP() - INTERVAL ? SECOND)')
            ->execute([max($windowSeconds, 3600)]);

        $statement = $this->db->connection()->prepare(
            'INSERT INTO a_attempt_counters(ac_key, ac_count, ac_window_start) VALUES(?, 1, UTC_TIMESTAMP()) '
            . 'ON DUPLICATE KEY UPDATE '
            . 'ac_count = IF(ac_window_start > (UTC_TIMESTAMP() - INTERVAL ? SECOND), ac_count + 1, 1), '
            . 'ac_window_start = IF(ac_window_start > (UTC_TIMESTAMP() - INTERVAL ? SECOND), ac_window_start, UTC_TIMESTAMP())'
        );
        $statement->execute([$key, $windowSeconds, $windowSeconds]);
    }

    public function reset(string $key): void
    {
        $this->db->connection()->prepare('DELETE FROM a_attempt_counters WHERE ac_key=?')->execute([$key]);
    }
}
