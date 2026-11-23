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
    /** Hash de una contraseña aleatoria: verificarlo cuesta lo mismo que una cuenta real. */
    private ?string $dummyHash = null;

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

    /**
     * Hace el mismo trabajo que verify() cuando la cuenta no existe, para que el
     * tiempo de respuesta no revele qué correos están registrados.
     */
    public function verifyAgainstDummy(string $password): void
    {
        $this->dummyHash ??= $this->hash(bin2hex(random_bytes(16)));
        password_verify($password, $this->dummyHash);
    }
}
