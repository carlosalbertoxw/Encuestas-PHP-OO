<?php

declare(strict_types=1);

namespace Encuestas\Data;

/**
 * Resultado de una operación de escritura. Distingue explícitamente los casos
 * que antes se colapsaban en un booleano, para que el controlador muestre el
 * mensaje correcto.
 */
enum RepositoryResult
{
    /** La operación afectó la fila esperada. */
    case Success;

    /** Violó una restricción de unicidad (correo o nombre de usuario ya en uso). */
    case Duplicate;

    /** No existe la fila objetivo (0 filas afectadas). */
    case NotFound;
}
