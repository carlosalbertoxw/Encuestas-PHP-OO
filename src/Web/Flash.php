<?php

declare(strict_types=1);

namespace Encuestas\Web;

/** Mensaje de una sola lectura que sobrevive a una redirección entre peticiones. */
final class Flash
{
    private const SESSION_KEY = 'flash_message';

    public static function set(string $message): void
    {
        $_SESSION[self::SESSION_KEY] = $message;
    }

    public static function pull(): ?string
    {
        $message = $_SESSION[self::SESSION_KEY] ?? null;
        unset($_SESSION[self::SESSION_KEY]);
        return $message === null ? null : (string) $message;
    }
}
