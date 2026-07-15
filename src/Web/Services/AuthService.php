<?php

declare(strict_types=1);

namespace Encuestas\Web\Services;

use Encuestas\Data\UserRepository;
use Encuestas\Infrastructure\Logger;
use Encuestas\Model\UserProfile;

/**
 * Concentra la lógica de autenticación (verificar credenciales, regenerar
 * hashes con parámetros antiguos, poblar la sesión) para mantenerla fuera de
 * los controladores y poder probarla.
 *
 * La sesión guarda el sello de seguridad del usuario y lo revalida contra la
 * BD como máximo cada STAMP_CHECK_SECONDS: si la contraseña cambió en otra
 * sesión, esta se cierra.
 */
class AuthService
{
    private const SESSION_KEY = 'auth';
    private const STAMP_CHECK_SECONDS = 30;

    public function __construct(
        private readonly UserRepository $users,
        private readonly PasswordService $passwords,
        private readonly AccountLockout $lockout,
        private readonly Logger $logger,
    ) {
    }

    public static function newSecurityStamp(): string
    {
        $bytes = random_bytes(16);
        // UUID v4 en formato canónico (36 caracteres).
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }

    public function login(string $email, string $password, string $remoteIp): LoginResult
    {
        if ($this->lockout->isLocked($email)) {
            $this->logger->warning("Inicio de sesión bloqueado por exceso de intentos para {$email}");
            return LoginResult::LockedOut;
        }

        $profile = $this->users->getProfileByEmail($email);
        $outcome = $profile === null
            ? PasswordVerificationOutcome::Failed
            : $this->passwords->verify($profile->user->passwordHash, $password);

        if ($profile === null || $outcome === PasswordVerificationOutcome::Failed) {
            $this->lockout->recordFailure($email);
            $this->logger->warning("Intento de inicio de sesión fallido para {$email} desde {$remoteIp}");
            return LoginResult::InvalidCredentials;
        }

        // Credenciales correctas: se limpia el contador de intentos fallidos.
        $this->lockout->reset($email);

        if (!$profile->user->emailConfirmed) {
            return LoginResult::EmailNotConfirmed;
        }

        if ($outcome === PasswordVerificationOutcome::SuccessRehashNeeded) {
            // El hash usa parámetros antiguos: se regenera de forma transparente.
            // No rota el sello de seguridad para no cerrar otras sesiones válidas.
            $this->users->updatePassword($profile->user->id, $this->passwords->hash($password));
            $this->logger->info("Hash de contraseña actualizado para el usuario {$profile->user->id}");
        }

        $this->signIn($profile);
        $this->logger->info("Inicio de sesión exitoso del usuario {$profile->user->id}");
        return LoginResult::Success;
    }

    /** Puebla la sesión con los datos actuales del perfil (regenerando el id de sesión). */
    public function signIn(UserProfile $profile): void
    {
        session_regenerate_id(true);
        $_SESSION[self::SESSION_KEY] = [
            'userId' => $profile->user->id,
            'userName' => $profile->userName,
            'name' => $profile->name,
            'email' => $profile->user->email,
            'securityStamp' => $profile->user->securityStamp,
            'stampCheckedAt' => time(),
        ];
    }

    /** Recarga los datos de sesión tras editar perfil, usuario, correo o contraseña. */
    public function refreshClaims(int $userId): void
    {
        $user = $this->users->getUser($userId);
        if ($user === null) {
            return;
        }
        $profile = $this->users->getProfileByEmail($user->email);
        if ($profile !== null) {
            $this->signIn($profile);
        }
    }

    public function signOut(): void
    {
        unset($_SESSION[self::SESSION_KEY]);
        session_regenerate_id(true);
    }

    public function isAuthenticated(): bool
    {
        return isset($_SESSION[self::SESSION_KEY]);
    }

    public function userId(): int
    {
        return (int) ($_SESSION[self::SESSION_KEY]['userId'] ?? 0);
    }

    public function userName(): string
    {
        return (string) ($_SESSION[self::SESSION_KEY]['userName'] ?? '');
    }

    public function name(): string
    {
        return (string) ($_SESSION[self::SESSION_KEY]['name'] ?? '');
    }

    public function email(): string
    {
        return (string) ($_SESSION[self::SESSION_KEY]['email'] ?? '');
    }

    /**
     * Rechaza la sesión si su sello de seguridad no coincide con el de la BD
     * (p. ej. tras un cambio de contraseña en otra sesión). Para no consultar
     * la BD en cada petición, se revalida como máximo cada 30 segundos.
     */
    public function validateSecurityStamp(): void
    {
        if (!$this->isAuthenticated()) {
            return;
        }
        $checkedAt = (int) ($_SESSION[self::SESSION_KEY]['stampCheckedAt'] ?? 0);
        if ($checkedAt > time() - self::STAMP_CHECK_SECONDS) {
            return;
        }

        $currentStamp = $this->users->getSecurityStamp($this->userId());
        if ($currentStamp === null || $currentStamp !== ($_SESSION[self::SESSION_KEY]['securityStamp'] ?? '')) {
            $this->signOut();
            return;
        }
        $_SESSION[self::SESSION_KEY]['stampCheckedAt'] = time();
    }
}
