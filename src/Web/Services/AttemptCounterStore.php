<?php

declare(strict_types=1);

namespace Encuestas\Web\Services;

/**
 * Contador de ventana fija compartido entre peticiones. La interfaz permite
 * usar un almacén en memoria en las pruebas unitarias.
 */
interface AttemptCounterStore
{
    /** Número de intentos registrados para la clave dentro de su ventana vigente. */
    public function count(string $key, int $windowSeconds): int;

    /** Registra un intento; si la ventana anterior caducó, empieza una nueva. */
    public function increment(string $key, int $windowSeconds): void;

    public function reset(string $key): void;
}
