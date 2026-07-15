<?php

declare(strict_types=1);

namespace Encuestas;

/**
 * Configuración de la aplicación a partir de variables de entorno, con un
 * archivo .env opcional para desarrollo local: ningún secreto queda
 * versionado en el repositorio.
 */
final class Config
{
    private static bool $envLoaded = false;

    public static function get(string $name, string $default = ''): string
    {
        self::loadEnvFile();
        $value = $_ENV[$name] ?? getenv($name);
        return ($value === false || $value === '') ? $default : (string) $value;
    }

    public static function environment(): string
    {
        return self::get('APP_ENV', 'production');
    }

    public static function isDevelopment(): bool
    {
        return self::environment() === 'development';
    }

    /**
     * Clave con la que se firman los tokens (confirmación de correo y
     * restablecimiento de contraseña). En desarrollo, si no se define APP_KEY,
     * se genera una vez y se conserva en var/app.key para que los enlaces
     * sigan siendo válidos entre peticiones.
     */
    public static function appKey(): string
    {
        $key = self::get('APP_KEY');
        if ($key !== '') {
            return $key;
        }
        if (!self::isDevelopment()) {
            throw new \RuntimeException(
                'Falta APP_KEY: define la variable de entorno con una clave aleatoria (por ejemplo: openssl rand -hex 32).'
            );
        }

        $file = self::basePath() . '/var/app.key';
        if (is_file($file)) {
            return trim((string) file_get_contents($file));
        }
        $key = bin2hex(random_bytes(32));
        if (@file_put_contents($file, $key) === false) {
            throw new \RuntimeException('No se pudo escribir var/app.key; define APP_KEY en el entorno.');
        }
        return $key;
    }

    public static function basePath(): string
    {
        return dirname(__DIR__);
    }

    /** Carga ./.env (clave=valor) sin sobrescribir variables ya definidas en el entorno. */
    private static function loadEnvFile(): void
    {
        if (self::$envLoaded) {
            return;
        }
        self::$envLoaded = true;

        $file = self::basePath() . '/.env';
        if (!is_file($file)) {
            return;
        }
        foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }
            [$name, $value] = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value, " \t\"'");
            if ($name !== '' && getenv($name) === false && !isset($_ENV[$name])) {
                $_ENV[$name] = $value;
            }
        }
    }
}
