<?php

declare(strict_types=1);

namespace Encuestas\Web;

use Encuestas\Web\Services\AuthService;

/**
 * Enrutador con tabla explícita de rutas {Controller}/{Action}/{id?}. Las
 * rutas marcadas con requiresAuth redirigen a /User/Index cuando no hay
 * sesión iniciada.
 */
final class Router
{
    /** @var array<string, array{handler: callable(int): void, requiresAuth: bool}> */
    private array $routes = [];

    public function __construct(private readonly AuthService $auth)
    {
    }

    /** @param callable(int): void $handler */
    public function get(string $path, callable $handler, bool $requiresAuth = false): void
    {
        $this->map('GET', $path, $handler, $requiresAuth);
    }

    /** @param callable(int): void $handler */
    public function post(string $path, callable $handler, bool $requiresAuth = false): void
    {
        $this->map('POST', $path, $handler, $requiresAuth);
    }

    public function dispatch(string $method, string $uriPath): void
    {
        $segments = array_values(array_filter(explode('/', trim($uriPath, '/')), static fn (string $s): bool => $s !== ''));

        $controller = strtolower($segments[0] ?? 'user');
        $action = strtolower($segments[1] ?? 'index');
        $id = (int) ($segments[2] ?? 0);

        $key = strtoupper($method) . ' ' . $controller . '/' . $action;
        $route = $this->routes[$key] ?? null;
        if ($route === null || count($segments) > 3) {
            throw new NotFoundException($uriPath);
        }

        if ($route['requiresAuth'] && !$this->auth->isAuthenticated()) {
            header('Location: /User/Index');
            return;
        }

        ($route['handler'])($id);
    }

    /** @param callable(int): void $handler */
    private function map(string $method, string $path, callable $handler, bool $requiresAuth): void
    {
        $this->routes[$method . ' ' . strtolower($path)] = [
            'handler' => $handler,
            'requiresAuth' => $requiresAuth,
        ];
    }
}
