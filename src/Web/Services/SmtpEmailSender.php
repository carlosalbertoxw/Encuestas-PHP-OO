<?php

declare(strict_types=1);

namespace Encuestas\Web\Services;

use RuntimeException;

/**
 * Cliente SMTP mínimo, sin dependencias: envía un mensaje HTML por conexión, con
 * STARTTLS y AUTH LOGIN opcionales. En local se usa con Mailpit; en producción, con
 * cualquier servidor SMTP con STARTTLS.
 */
final class SmtpEmailSender implements EmailSender
{
    private const TIMEOUT_SECONDS = 10;

    public function __construct(
        private readonly string $host,
        private readonly int $port,
        private readonly bool $startTls,
        private readonly string $user,
        private readonly string $password,
        private readonly string $from,
    ) {
    }

    public function send(string $recipient, string $subject, string $htmlBody): void
    {
        $errorCode = 0;
        $errorMessage = '';
        $socket = @stream_socket_client(
            "tcp://{$this->host}:{$this->port}",
            $errorCode,
            $errorMessage,
            self::TIMEOUT_SECONDS
        );
        if ($socket === false) {
            throw new RuntimeException("No se pudo conectar al servidor SMTP: {$errorMessage}");
        }
        stream_set_timeout($socket, self::TIMEOUT_SECONDS);

        try {
            $this->expect($socket, 220);
            $this->command($socket, 'EHLO encuestas', 250);
            if ($this->startTls) {
                $this->command($socket, 'STARTTLS', 220);
                if (stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT) !== true) {
                    throw new RuntimeException('No se pudo negociar TLS con el servidor SMTP');
                }
                $this->command($socket, 'EHLO encuestas', 250);
            }
            if ($this->user !== '') {
                $this->command($socket, 'AUTH LOGIN', 334);
                $this->command($socket, base64_encode($this->user), 334);
                $this->command($socket, base64_encode($this->password), 235);
            }
            $this->command($socket, 'MAIL FROM:<' . self::address($this->from) . '>', 250);
            $this->command($socket, 'RCPT TO:<' . self::address($recipient) . '>', 250);
            $this->command($socket, 'DATA', 354);
            $this->write($socket, $this->message($recipient, $subject, $htmlBody) . "\r\n.");
            $this->expect($socket, 250);
            $this->command($socket, 'QUIT', 221);
        } finally {
            fclose($socket);
        }
    }

    private function message(string $recipient, string $subject, string $htmlBody): string
    {
        $headers = [
            'From: ' . self::headerValue($this->from),
            'To: ' . self::headerValue($recipient),
            'Subject: =?UTF-8?B?' . base64_encode(self::headerValue($subject)) . '?=',
            'Date: ' . date(DATE_RFC2822),
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'Content-Transfer-Encoding: base64',
        ];
        $body = rtrim(chunk_split(base64_encode($htmlBody), 76, "\r\n"));
        return implode("\r\n", $headers) . "\r\n\r\n" . $body;
    }

    /** "Encuestas <no-reply@x>" → "no-reply@x". */
    private static function address(string $value): string
    {
        $value = self::headerValue($value);
        return preg_match('/<([^>]+)>/', $value, $matches) === 1 ? $matches[1] : $value;
    }

    /** Evita la inyección de cabeceras quitando los saltos de línea. */
    private static function headerValue(string $value): string
    {
        return trim(str_replace(["\r", "\n"], '', $value));
    }

    /** @param resource $socket */
    private function command($socket, string $line, int $expectedCode): void
    {
        $this->write($socket, $line);
        $this->expect($socket, $expectedCode);
    }

    /** @param resource $socket */
    private function write($socket, string $line): void
    {
        if (fwrite($socket, $line . "\r\n") === false) {
            throw new RuntimeException('Error al escribir en la conexión SMTP');
        }
    }

    /**
     * Lee la respuesta (posiblemente multilínea) y comprueba su código.
     *
     * @param resource $socket
     */
    private function expect($socket, int $expectedCode): void
    {
        $response = '';
        do {
            $line = fgets($socket, 1024);
            if ($line === false) {
                throw new RuntimeException('El servidor SMTP cerró la conexión');
            }
            $response .= $line;
        } while (strlen($line) > 3 && $line[3] === '-');

        if ((int) substr($response, 0, 3) !== $expectedCode) {
            throw new RuntimeException('Respuesta SMTP inesperada: ' . trim($response));
        }
    }
}
