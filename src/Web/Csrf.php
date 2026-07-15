<?php

declare(strict_types=1);

namespace Encuestas\Web;

/**
 * Protección CSRF para todos los POST: token por sesión que se incluye como
 * campo oculto en cada formulario y se compara en tiempo constante al recibir
 * la petición.
 */
final class Csrf
{
    private const SESSION_KEY = 'csrf_token';
    public const FIELD_NAME = '_csrf';

    public static function token(): string
    {
        if (!isset($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }
        return (string) $_SESSION[self::SESSION_KEY];
    }

    /** Campo oculto para incluir dentro de cada <form method="post">. */
    public static function field(): string
    {
        return '<input type="hidden" name="' . self::FIELD_NAME . '" value="'
            . htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8') . '">';
    }

    public static function validate(): bool
    {
        $sent = (string) ($_POST[self::FIELD_NAME] ?? '');
        return $sent !== '' && isset($_SESSION[self::SESSION_KEY])
            && hash_equals((string) $_SESSION[self::SESSION_KEY], $sent);
    }
}
