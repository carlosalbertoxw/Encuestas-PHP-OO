<?php

declare(strict_types=1);

namespace Encuestas\Web\Services;

enum LoginResult
{
    case Success;
    case InvalidCredentials;
    case EmailNotConfirmed;
    case LockedOut;
}
