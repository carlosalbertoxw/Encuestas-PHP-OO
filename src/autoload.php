<?php

declare(strict_types=1);

// Autoloader PSR-4 propio para que la aplicación funcione sin Composer en
// producción (composer.json existe para las dependencias de desarrollo, como
// PHPUnit). Si vendor/autoload.php está presente, también se registra.

spl_autoload_register(static function (string $class): void {
    $prefix = 'Encuestas\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $file = __DIR__ . '/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

require_once __DIR__ . '/Web/helpers.php';

$composerAutoload = dirname(__DIR__) . '/vendor/autoload.php';
if (is_file($composerAutoload)) {
    require_once $composerAutoload;
}
