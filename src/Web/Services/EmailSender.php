<?php

declare(strict_types=1);

namespace Encuestas\Web\Services;

/** Envío de correos transaccionales (confirmación de cuenta, restablecimiento). */
interface EmailSender
{
    public function send(string $recipient, string $subject, string $htmlBody): void;
}
