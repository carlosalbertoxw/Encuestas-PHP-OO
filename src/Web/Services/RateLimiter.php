<?php

declare(strict_types=1);

namespace Encuestas\Web\Services;

/**
 * Rate limiting de ventana fija por IP (10 peticiones por minuto) para
 * login/registro/recuperación, para frenar fuerza bruta y registro masivo.
 */
class RateLimiter
{
    public const PERMIT_LIMIT = 10;
    public const WINDOW_SECONDS = 60;

    public function __construct(private readonly AttemptCounterStore $store)
    {
    }

    /** Registra la petición y devuelve true si el cliente superó el límite. */
    public function isRejected(string $clientIp): bool
    {
        $key = 'auth:' . ($clientIp === '' ? 'desconocido' : $clientIp);
        if ($this->store->count($key, self::WINDOW_SECONDS) >= self::PERMIT_LIMIT) {
            return true;
        }
        $this->store->increment($key, self::WINDOW_SECONDS);
        return false;
    }
}
