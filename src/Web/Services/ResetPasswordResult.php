<?php

declare(strict_types=1);

namespace Encuestas\Web\Services;

/** Resultado de restablecer la contraseña con el enlace del correo. */
enum ResetPasswordResult
{
    case Success;
    /** Token manipulado, caducado o ya usado (el sello de seguridad cambió). */
    case InvalidToken;
    case Error;
}
