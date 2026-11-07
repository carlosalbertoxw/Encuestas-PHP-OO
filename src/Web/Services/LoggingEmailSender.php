<?php

declare(strict_types=1);

namespace Encuestas\Web\Services;

use Encuestas\Infrastructure\Logger;

/**
 * Escribe el correo en el log en lugar de enviarlo. Solo se usa en development
 * cuando no hay SMTP configurado (y en las pruebas); el cuerpo incluye enlaces
 * con tokens.
 */
final class LoggingEmailSender implements EmailSender
{
    public function __construct(private readonly Logger $logger)
    {
    }

    public function send(string $recipient, string $subject, string $htmlBody): void
    {
        $this->logger->info('[Correo simulado]', ['to' => $recipient, 'subject' => $subject, 'body' => $htmlBody]);
    }
}
