<?php

declare(strict_types=1);

namespace Encuestas\Web\Services;

use Encuestas\Config;
use RuntimeException;

/**
 * Cliente SMTP mínimo, sin dependencias: envía un mensaje HTML por conexión, con
 * cifrado STARTTLS, TLS implícito (puerto 465) o ninguno (solo servidores locales
 * como Mailpit), TLS 1.2 o 1.3 con verificación del certificado, y AUTH LOGIN
 * opcional.
 */
final class SmtpEmailSender implements EmailSender
{
    public const ENCRYPTION_STARTTLS = 'starttls';
    public const ENCRYPTION_TLS = 'tls';
    public const ENCRYPTION_NONE = 'none';

    private const TIMEOUT_SECONDS = 10;
    private const CRYPTO_METHOD = STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT;

    public function __construct(
        private readonly string $host,
        private readonly int $port,
        private readonly string $encryption,
        private readonly string $user,
        private readonly string $password,
        private readonly string $from,
    ) {
        if (!in_array($encryption, [self::ENCRYPTION_STARTTLS, self::ENCRYPTION_TLS, self::ENCRYPTION_NONE], true)) {
            throw new RuntimeException('SMTP_ENCRYPTION debe ser starttls, tls o none.');
        }
    }

    /**
     * Configuración desde el entorno. SMTP_HOST es obligatorio en todos los entornos
     * (en local, Mailpit): los correos llevan enlaces con tokens y nunca se escriben
     * en el log.
     */
    public static function fromEnvironment(): self
    {
        $host = Config::get('SMTP_HOST');
        if ($host === '') {
            throw new RuntimeException('Falta SMTP_HOST para enviar los correos (en local, el Mailpit de docker-compose).');
        }
        return new self(
            $host,
            (int) Config::get('SMTP_PORT', '587'),
            strtolower(Config::get('SMTP_ENCRYPTION', self::ENCRYPTION_STARTTLS)),
            Config::get('SMTP_USER'),
            Config::get('SMTP_PASSWORD'),
            Config::get('SMTP_FROM', 'Encuestas <no-reply@encuestas.local>'),
        );
    }

    public function send(string $recipient, string $subject, string $htmlBody): void
    {
        $errorCode = 0;
        $errorMessage = '';
        // El certificado se valida contra el nombre del servidor, también tras STARTTLS.
        $context = stream_context_create(['ssl' => [
            'peer_name' => $this->host,
            'verify_peer' => true,
            'verify_peer_name' => true,
            'SNI_enabled' => true,
        ]]);
        $socket = @stream_socket_client(
            "tcp://{$this->host}:{$this->port}",
            $errorCode,
            $errorMessage,
            self::TIMEOUT_SECONDS,
            STREAM_CLIENT_CONNECT,
            $context
        );
        if ($socket === false) {
            throw new RuntimeException("No se pudo conectar al servidor SMTP: {$errorMessage}");
        }
        stream_set_timeout($socket, self::TIMEOUT_SECONDS);

        try {
            if ($this->encryption === self::ENCRYPTION_TLS) {
                $this->enableTls($socket);
            }
            $this->expect($socket, 220);
            $this->command($socket, 'EHLO encuestas', 250);
            if ($this->encryption === self::ENCRYPTION_STARTTLS) {
                $this->command($socket, 'STARTTLS', 220);
                $this->enableTls($socket);
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

    /** @param resource $socket */
    private function enableTls($socket): void
    {
        if (stream_socket_enable_crypto($socket, true, self::CRYPTO_METHOD) !== true) {
            throw new RuntimeException('No se pudo negociar TLS con el servidor SMTP');
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
