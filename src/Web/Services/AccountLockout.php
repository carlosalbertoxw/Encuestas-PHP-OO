<?php

declare(strict_types=1);

namespace Encuestas\Web\Services;

/**
 * Bloqueo por cuenta: tras varios intentos de inicio de sesión fallidos sobre
 * el mismo correo se rechazan nuevos intentos durante una ventana, incluso con
 * la contraseña correcta. Complementa al rate limiting por IP.
 */
class AccountLockout
{
    public const MAX_ATTEMPTS = 5;
    public const WINDOW_SECONDS = 15 * 60;

    public function __construct(private readonly AttemptCounterStore $store)
    {
    }

    public function isLocked(string $email): bool
    {
        return $this->store->count(self::key($email), self::WINDOW_SECONDS) >= self::MAX_ATTEMPTS;
    }

    public function recordFailure(string $email): void
    {
        $this->store->increment(self::key($email), self::WINDOW_SECONDS);
    }

    public function reset(string $email): void
    {
        $this->store->reset(self::key($email));
    }

    private static function key(string $email): string
    {
        return 'lockout:' . mb_strtolower($email);
    }
}
