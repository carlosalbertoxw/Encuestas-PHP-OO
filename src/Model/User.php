<?php

declare(strict_types=1);

namespace Encuestas\Model;

/** Cuenta de acceso: credenciales y correo electrónico. */
final class User
{
    public int $id = 0;
    public string $email = '';

    /** Indica si el usuario confirmó su correo mediante el enlace enviado al registrarse. */
    public bool $emailConfirmed = false;

    /** Hash de la contraseña (password_hash de PHP, bcrypt/argon2 según PASSWORD_DEFAULT). */
    public string $passwordHash = '';

    /**
     * Valor que cambia cuando se modifican las credenciales. Se guarda en la sesión
     * y se valida periódicamente para invalidar sesiones abiertas tras un cambio
     * de contraseña.
     */
    public string $securityStamp = '';
}
