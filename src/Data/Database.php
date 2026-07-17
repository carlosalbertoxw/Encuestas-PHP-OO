<?php

declare(strict_types=1);

namespace Encuestas\Data;

use Encuestas\Config;
use PDO;

/**
 * Fábrica de conexiones PDO a MySQL. Una sola conexión perezosa por petición,
 * siempre con sentencias preparadas nativas y excepciones activadas.
 */
final class Database
{
    private ?PDO $connection = null;

    public function __construct(
        private readonly string $host,
        private readonly int $port,
        private readonly string $database,
        private readonly string $username,
        private readonly string $password,
    ) {
    }

    public static function fromEnvironment(): self
    {
        return new self(
            Config::get('DB_HOST', '127.0.0.1'),
            (int) Config::get('DB_PORT', '3307'),
            Config::get('DB_DATABASE', 'encuestas'),
            Config::get('DB_USERNAME', 'encuestas'),
            Config::get('DB_PASSWORD', 'encuestas'),
        );
    }

    public function connection(): PDO
    {
        if ($this->connection === null) {
            $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $this->host, $this->port, $this->database);
            // PDO::MYSQL_ATTR_FOUND_ROWS está deprecada desde PHP 8.5 en favor de
            // Pdo\Mysql::ATTR_FOUND_ROWS (que existe desde 8.4); el ternario evita
            // el aviso en 8.5 sin romper los PHP 8.2/8.3 que admite composer.json.
            $foundRows = PHP_VERSION_ID >= 80400
                ? \Pdo\Mysql::ATTR_FOUND_ROWS
                : PDO::MYSQL_ATTR_FOUND_ROWS;
            $this->connection = new PDO($dsn, $this->username, $this->password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                // rowCount() cuenta filas encontradas, no solo las modificadas, para
                // que un UPDATE idempotente (mismo valor) siga reportando Success.
                $foundRows => true,
            ]);
        }
        return $this->connection;
    }

    /** true cuando la excepción corresponde a una violación de clave única (MySQL 1062). */
    public static function isDuplicateKey(\PDOException $exception): bool
    {
        return ($exception->errorInfo[1] ?? 0) === 1062;
    }
}
