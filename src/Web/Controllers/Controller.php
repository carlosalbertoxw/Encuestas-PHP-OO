<?php

declare(strict_types=1);

namespace Encuestas\Web\Controllers;

use Encuestas\Web\RequestContext;
use Encuestas\Web\Services\AuthService;
use Encuestas\Web\View;

/** Utilidades compartidas por los controladores. */
abstract class Controller
{
    public function __construct(
        protected readonly View $view,
        protected readonly AuthService $auth,
        protected readonly RequestContext $request,
    ) {
    }

    protected function redirect(string $path): void
    {
        header('Location: ' . $path);
    }

    protected function input(string $name): string
    {
        return trim((string) ($_POST[$name] ?? ''));
    }

    /** Número de página del parámetro ?page= (1 si falta o no es válido). */
    protected function pageNumber(): int
    {
        return max(1, (int) ($this->query('page') ?? '1'));
    }

    protected function query(string $name): ?string
    {
        $value = $_GET[$name] ?? null;
        return $value === null ? null : (string) $value;
    }
}
