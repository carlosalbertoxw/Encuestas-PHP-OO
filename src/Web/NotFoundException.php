<?php

declare(strict_types=1);

namespace Encuestas\Web;

/** Señala que el recurso solicitado no existe; el front controller responde 404. */
final class NotFoundException extends \RuntimeException
{
}
