<?php

declare(strict_types=1);

namespace Encuestas\Infrastructure;

/**
 * Logging de eventos de la aplicación. Escribe en el log de errores de PHP,
 * que en el contenedor termina en la salida estándar de Apache (visible con
 * `docker compose logs web`).
 */
final class Logger
{
    public function info(string $message): void
    {
        $this->write('INFO', $message);
    }

    public function warning(string $message): void
    {
        $this->write('WARN', $message);
    }

    public function error(string $message): void
    {
        $this->write('ERROR', $message);
    }

    private function write(string $level, string $message): void
    {
        error_log(sprintf('[Encuestas] %s: %s', $level, $message));
    }
}
