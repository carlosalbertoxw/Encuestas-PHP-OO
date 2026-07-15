<?php

declare(strict_types=1);

namespace Encuestas\Infrastructure;

use Encuestas\Config;
use Encuestas\Data\Database;
use PDO;
use PDOException;

/**
 * Aplica los scripts SQL de la carpeta migrations/, en orden alfabético.
 * Lleva el registro de lo ya aplicado en la tabla `schemaversions`, por lo
 * que la operación es idempotente y el esquema de cualquier entorno
 * evoluciona de forma reproducible.
 */
final class MigrationRunner
{
    private const MAX_ATTEMPTS = 10;
    private const RETRY_DELAY_SECONDS = 3;

    public function __construct(
        private readonly Database $db,
        private readonly Logger $logger,
    ) {
    }

    public function run(): void
    {
        $pdo = $this->waitForDatabase();

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS schemaversions ('
            . 'schemaversionsid INT NOT NULL AUTO_INCREMENT, '
            . 'scriptname VARCHAR(255) NOT NULL, '
            . 'applied DATETIME NOT NULL, '
            . 'PRIMARY KEY (schemaversionsid)'
            . ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );

        $statement = $pdo->query('SELECT scriptname FROM schemaversions');
        $applied = $statement === false ? [] : $statement->fetchAll(PDO::FETCH_COLUMN);
        $pending = array_filter($this->scripts(), static fn (string $s): bool => !in_array(basename($s), $applied, true));

        if ($pending === []) {
            $this->logger->info('Base de datos al día; sin migraciones pendientes.');
            return;
        }

        $names = array_map('basename', $pending);
        $this->logger->info(sprintf('Aplicando %d migración(es): %s', count($pending), implode(', ', $names)));

        foreach ($pending as $script) {
            $this->apply($pdo, $script);
        }
    }

    /** @return list<string> Rutas de scripts ordenadas alfabéticamente. */
    private function scripts(): array
    {
        $files = glob(Config::basePath() . '/migrations/*.sql') ?: [];
        sort($files, SORT_STRING);
        return $files;
    }

    private function apply(PDO $pdo, string $script): void
    {
        try {
            // El DDL de MySQL confirma implícitamente, así que no hay transacción
            // real por script; si algo falla, el script no se registra y el error
            // debe corregirse antes de reintentar.
            foreach ($this->splitStatements((string) file_get_contents($script)) as $sql) {
                $pdo->exec($sql);
            }
            $statement = $pdo->prepare('INSERT INTO schemaversions(scriptname, applied) VALUES(?, UTC_TIMESTAMP())');
            $statement->execute([basename($script)]);
        } catch (PDOException $exception) {
            throw new \RuntimeException(
                sprintf("Falló la migración '%s': %s", basename($script), $exception->getMessage()),
                0,
                $exception
            );
        }
    }

    /**
     * Divide un script en sentencias individuales, ya que PDO::exec solo reporta
     * el error de la primera cuando recibe varias. Los scripts deben ser
     * sentencias separadas por ';' sin procedimientos ni triggers.
     *
     * @return list<string>
     */
    private function splitStatements(string $sql): array
    {
        $withoutComments = preg_replace('/^\s*--.*$/m', '', $sql) ?? $sql;
        $statements = [];
        foreach (explode(';', $withoutComments) as $statement) {
            $statement = trim($statement);
            if ($statement !== '') {
                $statements[] = $statement;
            }
        }
        return $statements;
    }

    // El contenedor de MySQL puede tardar unos segundos en aceptar conexiones tras `docker compose up`.
    private function waitForDatabase(): PDO
    {
        for ($attempt = 1;; $attempt++) {
            try {
                return $this->db->connection();
            } catch (PDOException $exception) {
                if ($attempt >= self::MAX_ATTEMPTS) {
                    throw $exception;
                }
                $this->logger->warning(sprintf(
                    'Base de datos no disponible (intento %d/%d): %s',
                    $attempt,
                    self::MAX_ATTEMPTS,
                    $exception->getMessage()
                ));
                sleep(self::RETRY_DELAY_SECONDS);
            }
        }
    }
}
