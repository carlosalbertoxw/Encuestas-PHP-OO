<?php

declare(strict_types=1);

namespace Encuestas\Model;

/** Encuesta creada por un usuario. */
final class Poll
{
    public int $id = 0;
    public string $title = '';
    public string $description = '';

    /** Orden de aparición en el tablero y en el perfil público. */
    public int $position = 0;

    public int $userId = 0;
}
