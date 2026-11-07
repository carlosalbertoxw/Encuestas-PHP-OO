<?php

declare(strict_types=1);

namespace Encuestas\Infrastructure;

/**
 * Logging de eventos de la aplicación en JSON (una línea por evento) con nivel,
 * mensaje, contexto y el identificador de la petición. Escribe en la salida de
 * errores, que en el contenedor termina en `docker compose logs web`.
 */
final class Logger
{
    /** @var resource|null */
    private $stream;

    /**
     * @param string $requestId Identificador de la petición (cabecera X-Request-Id).
     * @param string $destination Flujo de salida; las pruebas pueden redirigirlo.
     */
    public function __construct(
        private readonly string $requestId = '',
        private readonly string $destination = 'php://stderr',
    ) {
    }

    /** @param array<string, scalar|null> $context */
    public function info(string $message, array $context = []): void
    {
        $this->write('INFO', $message, $context);
    }

    /** @param array<string, scalar|null> $context */
    public function warning(string $message, array $context = []): void
    {
        $this->write('WARNING', $message, $context);
    }

    /** @param array<string, scalar|null> $context */
    public function error(string $message, array $context = []): void
    {
        $this->write('ERROR', $message, $context);
    }

    public function requestId(): string
    {
        return $this->requestId;
    }

    /** @param array<string, scalar|null> $context */
    private function write(string $level, string $message, array $context): void
    {
        $entry = ['timestamp' => gmdate('Y-m-d\TH:i:s\Z'), 'level' => $level, 'message' => $message];
        if ($this->requestId !== '') {
            $entry['requestId'] = $this->requestId;
        }
        if ($context !== []) {
            $entry['context'] = $context;
        }
        $line = json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
        if ($this->stream === null) {
            $stream = fopen($this->destination, 'ab');
            $this->stream = $stream === false ? null : $stream;
        }
        if ($this->stream !== null && $line !== false) {
            fwrite($this->stream, $line . "\n");
        }
    }
}
