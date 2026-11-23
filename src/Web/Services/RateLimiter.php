<?php

declare(strict_types=1);

namespace Encuestas\Web\Services;

/**
 * Límite de peticiones de ventana fija por clave (una IP, un usuario…), con los
 * contadores en MySQL para que se compartan entre procesos y réplicas.
 */
class RateLimiter
{
    public function __construct(
        private readonly AttemptCounterStore $store,
        private readonly string $prefix,
        private readonly int $permitLimit,
        private readonly int $windowSeconds,
    ) {
    }

    /** Registra la petición y devuelve true si la clave superó el límite. */
    public function isRejected(string $key): bool
    {
        $fullKey = $this->prefix . ':' . ($key === '' ? 'desconocido' : $key);
        if ($this->store->count($fullKey, $this->windowSeconds) >= $this->permitLimit) {
            return true;
        }
        $this->store->increment($fullKey, $this->windowSeconds);
        return false;
    }
}
