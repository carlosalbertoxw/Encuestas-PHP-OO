<?php

declare(strict_types=1);

namespace Encuestas\Model;

/** Respuesta de un usuario a una encuesta: calificación de 1 a 5 estrellas y comentario opcional. */
final class Answer
{
    public int $id = 0;
    public int $stars = 0;
    public string $comment = '';
    public int $pollId = 0;
    public int $userId = 0;

    /** Nombre de usuario de quien respondió (solo lectura, para listados). */
    public string $userName = '';
}
