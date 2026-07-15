<?php

declare(strict_types=1);

namespace Encuestas\Web\Services;

use Encuestas\Infrastructure\Logger;

/**
 * Implementación por defecto que registra el correo en el log en lugar de
 * enviarlo. Sirve para desarrollo y pruebas; en producción debe sustituirse
 * por un proveedor real (SMTP, SendGrid…).
 */
final class LoggingEmailSender implements EmailSender
{
    public function __construct(private readonly Logger $logger)
    {
    }

    public function send(string $recipient, string $subject, string $htmlBody): void
    {
        $this->logger->info("[Correo simulado] Para: {$recipient} | Asunto: {$subject} | {$htmlBody}");
    }
}
