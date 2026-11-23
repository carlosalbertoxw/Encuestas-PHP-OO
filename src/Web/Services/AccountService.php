<?php

declare(strict_types=1);

namespace Encuestas\Web\Services;

use Encuestas\Config;
use Encuestas\Data\EmailOutbox;
use Encuestas\Data\RepositoryResult;
use Encuestas\Data\UserRepository;
use Encuestas\Infrastructure\Logger;
use Encuestas\Model\UserProfile;

/**
 * Flujos de la cuenta que combinan tokens, correos y el sello de seguridad:
 * registro y confirmación, recuperación de contraseña, cambio de correo y de
 * contraseña, y borrado. Los controladores solo validan la entrada, llaman aquí y
 * eligen el mensaje; lo relativo a la sesión sigue en AuthService.
 */
final class AccountService
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly PasswordService $passwords,
        private readonly TokenService $tokens,
        private readonly EmailOutbox $outbox,
        private readonly Logger $logger,
    ) {
    }

    /** Crea la cuenta y encola el enlace de confirmación. Si el correo ya existe no hace nada. */
    public function register(string $email, string $password): void
    {
        $result = $this->users->createUser($email, $this->passwords->hash($password), AuthService::newSecurityStamp());
        $profile = $result === RepositoryResult::Success ? $this->users->getProfileByEmail($email) : null;
        if ($profile !== null) {
            $this->sendConfirmation($profile);
            $this->logger->info('Cuenta nueva registrada', ['userId' => $profile->user->id]);
        }
    }

    /** Reenvía el enlace de confirmación solo si la cuenta existe y sigue sin confirmar. */
    public function resendConfirmation(string $email): void
    {
        $profile = $this->users->getProfileByEmail($email);
        if ($profile !== null && !$profile->user->emailConfirmed) {
            $this->sendConfirmation($profile);
            $this->logger->info('Enlace de confirmación reenviado', ['userId' => $profile->user->id]);
        }
    }

    public function confirmEmail(string $token): bool
    {
        $userId = $this->tokens->validateEmailConfirmationToken($token);
        if ($userId === null) {
            return false;
        }
        $this->users->confirmEmail($userId);
        $this->logger->info('Correo confirmado', ['userId' => $userId]);
        return true;
    }

    public function requestPasswordReset(string $email): void
    {
        $profile = $this->users->getProfileByEmail($email);
        if ($profile === null) {
            return;
        }
        $token = $this->tokens->createPasswordResetToken($profile->user->id, $profile->user->securityStamp);
        $this->outbox->enqueue(
            $email,
            'Restablece tu contraseña',
            EmailTemplates::link('Para restablecer tu contraseña visita:', self::link('/User/ResetPassword', $token))
        );
        $this->logger->info('Enlace de restablecimiento generado', ['userId' => $profile->user->id]);
    }

    public function isPasswordResetTokenValid(string $token): bool
    {
        return $this->tokens->validatePasswordResetToken($token) !== null;
    }

    public function resetPassword(string $token, string $newPassword): ResetPasswordResult
    {
        // El token incluye el sello de seguridad: si no coincide con el actual, ya se usó o caducó.
        $parsed = $this->tokens->validatePasswordResetToken($token);
        $stamp = $parsed === null ? null : $this->users->getSecurityStamp($parsed['userId']);
        if ($parsed === null || $stamp === null || $stamp !== $parsed['securityStamp']) {
            return ResetPasswordResult::InvalidToken;
        }

        $result = $this->users->changePassword(
            $parsed['userId'],
            $this->passwords->hash($newPassword),
            AuthService::newSecurityStamp()
        );
        if ($result !== RepositoryResult::Success) {
            return ResetPasswordResult::Error;
        }
        $this->logger->info('Contraseña restablecida', ['userId' => $parsed['userId']]);
        return ResetPasswordResult::Success;
    }

    public function verifyPassword(int $userId, string $password): bool
    {
        $user = $this->users->getUser($userId);
        return $user !== null
            && $this->passwords->verify($user->passwordHash, $password) !== PasswordVerificationOutcome::Failed;
    }

    /**
     * Deja $newEmail pendiente y envía el enlace a esa dirección, avisando al correo
     * actual. Si otra cuenta ya lo usa, solo se avisa a ese buzón: la respuesta en
     * pantalla es la misma, para no revelar qué correos tienen cuenta.
     */
    public function requestEmailChange(int $userId, string $currentEmail, string $newEmail): void
    {
        if ($this->users->getProfileByEmail($newEmail) !== null) {
            $this->outbox->enqueue($newEmail, 'Intento de usar tu correo', EmailTemplates::text(
                'Alguien intentó cambiar el correo de otra cuenta de Encuestas a esta dirección, pero ya tienes '
                . 'una cuenta con ella. No es necesario que hagas nada.'
            ));
            return;
        }
        if ($this->users->setPendingEmail($userId, $newEmail) !== RepositoryResult::Success) {
            return;
        }
        $token = $this->tokens->createEmailChangeToken($userId, $newEmail);
        $this->outbox->enqueue(
            $newEmail,
            'Confirma tu nuevo correo',
            EmailTemplates::link(
                'Para usar esta dirección en tu cuenta de Encuestas visita:',
                self::link('/Account/ConfirmEmailChange', $token)
            )
        );
        $this->outbox->enqueue($currentEmail, 'Solicitud de cambio de correo', EmailTemplates::text(
            'Se solicitó cambiar el correo de tu cuenta de Encuestas. Si no fuiste tú, cambia tu contraseña: '
            . 'el cambio no se aplicará mientras nadie abra el enlace enviado al correo nuevo.'
        ));
        $this->logger->info('Cambio de correo solicitado', ['userId' => $userId]);
    }

    /**
     * Aplica el correo pendiente y rota el sello de seguridad, lo que cierra todas
     * las sesiones. NotFound si el token no es válido o el cambio ya no está pendiente.
     */
    public function confirmEmailChange(string $token): RepositoryResult
    {
        $change = $this->tokens->validateEmailChangeToken($token);
        if ($change === null) {
            return RepositoryResult::NotFound;
        }
        $result = $this->users->confirmEmailChange($change['userId'], $change['email'], AuthService::newSecurityStamp());
        if ($result === RepositoryResult::Success) {
            $this->logger->info('Correo actualizado', ['userId' => $change['userId']]);
        }
        return $result;
    }

    /** Cambia la contraseña y rota el sello de seguridad para cerrar las demás sesiones. */
    public function changePassword(int $userId, string $newPassword): bool
    {
        $result = $this->users->changePassword($userId, $this->passwords->hash($newPassword), AuthService::newSecurityStamp());
        if ($result !== RepositoryResult::Success) {
            return false;
        }
        $this->logger->info('Contraseña cambiada', ['userId' => $userId]);
        return true;
    }

    public function deleteAccount(int $userId): bool
    {
        if ($this->users->deleteAccount($userId) !== RepositoryResult::Success) {
            return false;
        }
        $this->logger->info('Cuenta eliminada', ['userId' => $userId]);
        return true;
    }

    private function sendConfirmation(UserProfile $profile): void
    {
        $token = $this->tokens->createEmailConfirmationToken($profile->user->id);
        $this->outbox->enqueue(
            $profile->user->email,
            'Confirma tu cuenta',
            EmailTemplates::link('Para confirmar tu cuenta visita:', self::link('/User/ConfirmEmail', $token))
        );
    }

    private static function link(string $path, string $token): string
    {
        return Config::absoluteUrl($path . '?token=' . urlencode($token));
    }
}
