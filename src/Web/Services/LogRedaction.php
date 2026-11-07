<?php

declare(strict_types=1);

namespace Encuestas\Web\Services;

/** Enmascara datos personales antes de escribirlos en los logs. */
final class LogRedaction
{
    /** demo@encuestas.dev → d***@encuestas.dev. Basta para correlacionar intentos sin exponer el correo. */
    public static function email(string $email): string
    {
        if ($email === '') {
            return '';
        }
        $at = strpos($email, '@');
        return $at === false || $at === 0 ? '***' : $email[0] . '***' . substr($email, $at);
    }
}
