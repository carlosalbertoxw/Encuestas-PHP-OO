<?php

declare(strict_types=1);

namespace Encuestas\Web\Services;

enum PasswordVerificationOutcome
{
    case Failed;
    case Success;

    /** La contraseña es correcta pero el hash usa parámetros antiguos y conviene regenerarlo. */
    case SuccessRehashNeeded;
}
