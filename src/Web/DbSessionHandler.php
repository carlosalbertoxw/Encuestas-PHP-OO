<?php

declare(strict_types=1);

namespace Encuestas\Web;

use Encuestas\Data\Database;

/**
 * Sesiones almacenadas en MySQL (tabla a_sessions) en lugar del sistema de
 * archivos del contenedor: la sesión sobrevive a reinicios y despliegues, y
 * varias réplicas de la aplicación pueden compartirla.
 */
final class DbSessionHandler implements \SessionHandlerInterface
{
    public function __construct(
        private readonly Database $db,
        private readonly int $lifetimeSeconds,
    ) {
    }

    public function open(string $path, string $name): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read(string $id): string
    {
        $statement = $this->db->connection()->prepare(
            'SELECT s_data FROM a_sessions WHERE s_key=? AND s_last_activity > (UTC_TIMESTAMP() - INTERVAL ? SECOND)'
        );
        $statement->execute([$id, $this->lifetimeSeconds]);
        $data = $statement->fetchColumn();
        return $data === false ? '' : (string) $data;
    }

    public function write(string $id, string $data): bool
    {
        $statement = $this->db->connection()->prepare(
            'INSERT INTO a_sessions(s_key, s_data, s_last_activity) VALUES(?, ?, UTC_TIMESTAMP()) '
            . 'ON DUPLICATE KEY UPDATE s_data=VALUES(s_data), s_last_activity=UTC_TIMESTAMP()'
        );
        return $statement->execute([$id, $data]);
    }

    public function destroy(string $id): bool
    {
        return $this->db->connection()->prepare('DELETE FROM a_sessions WHERE s_key=?')->execute([$id]);
    }

    public function gc(int $max_lifetime): int
    {
        $statement = $this->db->connection()->prepare(
            'DELETE FROM a_sessions WHERE s_last_activity < (UTC_TIMESTAMP() - INTERVAL ? SECOND)'
        );
        $statement->execute([$this->lifetimeSeconds]);
        return $statement->rowCount();
    }
}
