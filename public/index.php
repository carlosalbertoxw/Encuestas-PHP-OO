<?php

declare(strict_types=1);

// Front controller: toda petición que no sea un archivo estático entra aquí
// (ver public/.htaccess para Apache). La atiende Encuestas\Web\Application.

use Encuestas\Config;
use Encuestas\Infrastructure\Logger;
use Encuestas\Web\Application;

// Con el servidor embebido de PHP (php -S) los archivos existentes se sirven tal cual.
// Solo se aceptan rutas que, ya resueltas, sigan dentro de public/.
if (PHP_SAPI === 'cli-server') {
    $file = realpath(__DIR__ . parse_url((string) $_SERVER['REQUEST_URI'], PHP_URL_PATH));
    // nosemgrep: php.lang.security.injection.tainted-filename.tainted-filename -- ruta confinada a public/
    if ($file !== false && str_starts_with($file, __DIR__ . DIRECTORY_SEPARATOR) && is_file($file)) {
        return false;
    }
}

require dirname(__DIR__) . '/vendor/autoload.php';

ini_set('display_errors', Config::isDevelopment() ? '1' : '0');
error_reporting(E_ALL);

// Identificador de la petición: viaja en la cabecera X-Request-Id y en cada línea de log.
$logger = new Logger(bin2hex(random_bytes(8)));
header('X-Request-Id: ' . $logger->requestId());

(new Application($logger))->handle(
    strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')),
    (string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?? '/'),
);
