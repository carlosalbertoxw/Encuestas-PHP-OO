<?php

declare(strict_types=1);

namespace Encuestas\Web\Services;

/**
 * Hashing y verificación de contraseñas con password_hash (PASSWORD_DEFAULT:
 * bcrypt o argon2 según la versión de PHP). Cuando PHP endurece sus parámetros
 * por defecto, verify() señala con SuccessRehashNeeded que el hash almacenado
 * debe regenerarse; el flujo de inicio de sesión lo actualiza de forma
 * transparente.
 */
class PasswordService
{
    public function hash(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    public function verify(string $storedHash, string $password): PasswordVerificationOutcome
    {
        // Un hash corrupto o en formato desconocido se trata como credencial
        // inválida (password_verify devuelve false sin lanzar excepción).
        if (!password_verify($password, $storedHash)) {
            return PasswordVerificationOutcome::Failed;
        }
        return password_needs_rehash($storedHash, PASSWORD_DEFAULT)
            ? PasswordVerificationOutcome::SuccessRehashNeeded
            : PasswordVerificationOutcome::Success;
    }
}
