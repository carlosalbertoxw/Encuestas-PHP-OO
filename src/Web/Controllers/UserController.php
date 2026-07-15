<?php

declare(strict_types=1);

namespace Encuestas\Web\Controllers;

use Encuestas\Data\PollRepository;
use Encuestas\Data\RepositoryResult;
use Encuestas\Data\UserRepository;
use Encuestas\Infrastructure\Logger;
use Encuestas\Web\Flash;
use Encuestas\Web\NotFoundException;
use Encuestas\Web\RequestContext;
use Encuestas\Web\Services\AuthService;
use Encuestas\Web\Services\EmailSender;
use Encuestas\Web\Services\LoginResult;
use Encuestas\Web\Services\Messages;
use Encuestas\Web\Services\PasswordService;
use Encuestas\Web\Services\PasswordVerificationOutcome;
use Encuestas\Web\Services\RateLimiter;
use Encuestas\Web\Services\TokenService;
use Encuestas\Web\Validator;
use Encuestas\Web\View;

/** Acceso, registro, recuperación de contraseña y gestión de la cuenta. */
final class UserController extends Controller
{
    public function __construct(
        View $view,
        AuthService $auth,
        RequestContext $request,
        private readonly UserRepository $users,
        private readonly PollRepository $polls,
        private readonly PasswordService $passwords,
        private readonly TokenService $tokens,
        private readonly EmailSender $email,
        private readonly RateLimiter $rateLimiter,
        private readonly Logger $logger,
    ) {
        parent::__construct($view, $auth, $request);
    }

    /** Página de inicio: acceso/registro para visitantes, o perfil público si se pasa ?profile=. */
    public function index(): void
    {
        if (!$this->auth->isAuthenticated()) {
            $this->view->render('user/index', 'Inicio', [], Flash::pull());
            return;
        }

        $profileName = $this->query('profile');
        if ($profileName === null) {
            $this->redirect('/Poll/Index');
            return;
        }

        $profile = $this->users->getProfileByUserName($profileName);
        if ($profile === null) {
            throw new NotFoundException($profileName);
        }

        $this->view->render('user/profile', "{$profile->name} ({$profile->userName})", [
            'polls' => $this->polls->getPolls($profile->user->id),
        ], Flash::pull());
    }

    public function login(): void
    {
        if ($this->rejectIfRateLimited()) {
            return;
        }
        if ($this->auth->isAuthenticated()) {
            $this->redirect('/Poll/Index');
            return;
        }

        $email = $this->input('Email');
        $password = $this->input('Password');

        // Sin longitud mínima: cuentas legadas podrían tener contraseñas más
        // cortas que la política actual.
        $validator = (new Validator())
            ->required('Email', $email)->email('Email', $email)->length('Email', $email, 0, 50)
            ->required('Password', $password)->length('Password', $password, 0, 50);
        if (!$validator->isValid()) {
            $this->view->render('user/index', 'Inicio', [
                'loginErrors' => $validator->errors(),
                'loginEmail' => $email,
            ], Messages::INVALID_CREDENTIALS);
            return;
        }

        $result = $this->auth->login($email, $password, $this->request->clientIp());
        if ($result === LoginResult::Success) {
            $this->redirect('/Poll/Index');
            return;
        }

        $message = match ($result) {
            LoginResult::EmailNotConfirmed => Messages::EMAIL_NOT_CONFIRMED,
            LoginResult::LockedOut => Messages::ACCOUNT_LOCKED,
            default => Messages::INVALID_CREDENTIALS,
        };
        $this->view->render('user/index', 'Inicio', ['loginEmail' => $email], $message);
    }

    public function register(): void
    {
        if ($this->rejectIfRateLimited()) {
            return;
        }
        if ($this->auth->isAuthenticated()) {
            $this->redirect('/Poll/Index');
            return;
        }

        $email = $this->input('Email');
        $password = $this->input('Password');
        $rePassword = $this->input('RePassword');

        $validator = (new Validator())
            ->required('Email', $email)->email('Email', $email)->length('Email', $email, 0, 50)
            ->required('Password', $password)->length('Password', $password, 6, 50)
            ->required('RePassword', $rePassword)->match('RePassword', $rePassword, $password);
        if (!$validator->isValid()) {
            $this->view->render('user/index', 'Inicio', [
                'registerErrors' => $validator->errors(),
                'registerEmail' => $email,
            ]);
            return;
        }

        $result = $this->users->createUser($email, $this->passwords->hash($password), AuthService::newSecurityStamp());
        if ($result === RepositoryResult::Success) {
            $profile = $this->users->getProfileByEmail($email);
            if ($profile !== null) {
                $this->sendConfirmationEmail($email, $profile->user->id);
            }
            $this->logger->info("Cuenta nueva registrada para {$email}");
        }
        // Se responde lo mismo exista o no el correo, para no revelar cuentas.
        $this->view->render('user/index', 'Inicio', [], Messages::REGISTER_ACKNOWLEDGED);
    }

    public function confirmEmail(): void
    {
        $token = $this->query('token');
        $userId = $token === null ? null : $this->tokens->validateEmailConfirmationToken($token);
        if ($userId === null) {
            Flash::set(Messages::EMAIL_CONFIRMATION_INVALID);
        } else {
            $this->users->confirmEmail($userId);
            $this->logger->info("Correo confirmado para el usuario {$userId}");
            Flash::set(Messages::EMAIL_CONFIRMED);
        }
        $this->redirect('/User/Index');
    }

    public function forgotPassword(): void
    {
        $this->view->render('user/forgot_password', 'Recuperar contraseña');
    }

    public function forgotPasswordPost(): void
    {
        if ($this->rejectIfRateLimited()) {
            return;
        }

        $email = $this->input('Email');
        $validator = (new Validator())
            ->required('Email', $email)->email('Email', $email)->length('Email', $email, 0, 50);
        if (!$validator->isValid()) {
            $this->view->render('user/forgot_password', 'Recuperar contraseña', [
                'errors' => $validator->errors(),
                'email' => $email,
            ]);
            return;
        }

        $profile = $this->users->getProfileByEmail($email);
        if ($profile !== null) {
            $token = $this->tokens->createPasswordResetToken($profile->user->id, $profile->user->securityStamp);
            $link = $this->request->absoluteUrl('/User/ResetPassword?token=' . urlencode($token));
            $this->email->send(
                $email,
                'Restablece tu contraseña',
                "Para restablecer tu contraseña visita: <a href=\"{$link}\">{$link}</a>"
            );
            $this->logger->info("Enlace de restablecimiento generado para el usuario {$profile->user->id}");
        }
        // Mensaje genérico para no revelar si el correo existe.
        $this->view->render('user/forgot_password', 'Recuperar contraseña', [], Messages::PASSWORD_RESET_SENT);
    }

    public function resetPassword(): void
    {
        $token = $this->query('token');
        if ($token === null || $this->tokens->validatePasswordResetToken($token) === null) {
            $this->view->render(
                'user/reset_password',
                'Restablecer contraseña',
                ['token' => ''],
                Messages::PASSWORD_RESET_INVALID
            );
            return;
        }
        $this->view->render('user/reset_password', 'Restablecer contraseña', ['token' => $token]);
    }

    public function resetPasswordPost(): void
    {
        $token = $this->input('Token');
        $newPassword = $this->input('NewPassword');
        $reNewPassword = $this->input('ReNewPassword');

        $validator = (new Validator())
            ->required('Token', $token)
            ->required('NewPassword', $newPassword)->length('NewPassword', $newPassword, 6, 50)
            ->required('ReNewPassword', $reNewPassword)->match('ReNewPassword', $reNewPassword, $newPassword);
        if (!$validator->isValid()) {
            $this->view->render('user/reset_password', 'Restablecer contraseña', [
                'token' => $token,
                'errors' => $validator->errors(),
            ]);
            return;
        }

        // El token incluye el sello de seguridad: si no coincide con el actual, ya se usó o caducó.
        $parsed = $this->tokens->validatePasswordResetToken($token);
        $stamp = $parsed === null ? null : $this->users->getSecurityStamp($parsed['userId']);
        if ($parsed === null || $stamp === null || $stamp !== $parsed['securityStamp']) {
            $this->view->render(
                'user/reset_password',
                'Restablecer contraseña',
                ['token' => $token],
                Messages::PASSWORD_RESET_INVALID
            );
            return;
        }

        $result = $this->users->changePassword(
            $parsed['userId'],
            $this->passwords->hash($newPassword),
            AuthService::newSecurityStamp()
        );
        if ($result === RepositoryResult::Success) {
            $this->logger->info("Contraseña restablecida para el usuario {$parsed['userId']}");
            Flash::set(Messages::PASSWORD_RESET_OK);
            $this->redirect('/User/Index');
            return;
        }

        $this->view->render('user/reset_password', 'Restablecer contraseña', ['token' => $token], Messages::UPDATE_ERROR);
    }

    public function editProfile(): void
    {
        $this->view->render('user/edit_profile', 'Editar perfil', ['name' => $this->auth->name()]);
    }

    public function editProfilePost(): void
    {
        $name = $this->input('Name');
        $validator = (new Validator())->required('Name', $name)->length('Name', $name, 0, 50);
        if (!$validator->isValid()) {
            $this->view->render('user/edit_profile', 'Editar perfil', ['name' => $name, 'errors' => $validator->errors()]);
            return;
        }

        $message = $this->users->updateName($this->auth->userId(), $name) === RepositoryResult::Success
            ? $this->refreshAndConfirm()
            : Messages::UPDATE_ERROR;
        $this->view->render('user/edit_profile', 'Editar perfil', ['name' => $name], $message);
    }

    public function changeUser(): void
    {
        $this->view->render('user/change_user', 'Cambiar usuario', ['userName' => $this->auth->userName()]);
    }

    public function changeUserPost(): void
    {
        $userName = $this->input('UserName');
        $password = $this->input('Password');

        $validator = (new Validator())
            ->required('UserName', $userName)->length('UserName', $userName, 0, 25)
            ->pattern('UserName', $userName, '/^[0-9a-zA-Z-]+$/', 'Solo letras, números y guiones')
            ->required('Password', $password)->length('Password', $password, 0, 50);
        if (!$validator->isValid()) {
            $this->view->render('user/change_user', 'Cambiar usuario', [
                'userName' => $userName,
                'errors' => $validator->errors(),
            ]);
            return;
        }

        if (!$this->verifyCurrentPassword($password)) {
            $this->view->render('user/change_user', 'Cambiar usuario', ['userName' => $userName], Messages::WRONG_PASSWORD);
            return;
        }

        $message = match ($this->users->updateUserName($this->auth->userId(), $userName)) {
            RepositoryResult::Success => $this->refreshAndConfirm(),
            RepositoryResult::Duplicate => Messages::USER_NAME_TAKEN,
            default => Messages::UPDATE_ERROR,
        };
        $this->view->render('user/change_user', 'Cambiar usuario', ['userName' => $userName], $message);
    }

    public function changeEmail(): void
    {
        $this->view->render('user/change_email', 'Cambiar correo', ['email' => $this->auth->email()]);
    }

    public function changeEmailPost(): void
    {
        $email = $this->input('Email');
        $password = $this->input('Password');

        $validator = (new Validator())
            ->required('Email', $email)->email('Email', $email)->length('Email', $email, 0, 50)
            ->required('Password', $password)->length('Password', $password, 0, 50);
        if (!$validator->isValid()) {
            $this->view->render('user/change_email', 'Cambiar correo', ['email' => $email, 'errors' => $validator->errors()]);
            return;
        }

        if (!$this->verifyCurrentPassword($password)) {
            $this->view->render('user/change_email', 'Cambiar correo', ['email' => $email], Messages::WRONG_PASSWORD);
            return;
        }

        // No distinguir "correo ya registrado" para no revelar cuentas ajenas.
        $message = match ($this->users->updateEmail($this->auth->userId(), $email)) {
            RepositoryResult::Success => $this->refreshAndConfirm(logEmailChange: true),
            default => Messages::UPDATE_ERROR,
        };
        $this->view->render('user/change_email', 'Cambiar correo', ['email' => $email], $message);
    }

    public function changePassword(): void
    {
        $this->view->render('user/change_password', 'Cambiar contraseña');
    }

    public function changePasswordPost(): void
    {
        $newPassword = $this->input('NewPassword');
        $reNewPassword = $this->input('ReNewPassword');
        $password = $this->input('Password');

        $validator = (new Validator())
            ->required('NewPassword', $newPassword)->length('NewPassword', $newPassword, 6, 50)
            ->required('ReNewPassword', $reNewPassword)->match('ReNewPassword', $reNewPassword, $newPassword)
            ->required('Password', $password)->length('Password', $password, 0, 50);
        if (!$validator->isValid()) {
            $this->view->render('user/change_password', 'Cambiar contraseña', ['errors' => $validator->errors()]);
            return;
        }

        if (!$this->verifyCurrentPassword($password)) {
            $this->view->render('user/change_password', 'Cambiar contraseña', [], Messages::WRONG_PASSWORD);
            return;
        }

        // Rota el sello de seguridad para cerrar otras sesiones; luego recarga la
        // sesión actual con el sello nuevo para no expulsar al propio usuario.
        $userId = $this->auth->userId();
        $result = $this->users->changePassword($userId, $this->passwords->hash($newPassword), AuthService::newSecurityStamp());
        if ($result === RepositoryResult::Success) {
            $this->auth->refreshClaims($userId);
            $this->logger->info("Contraseña cambiada por el usuario {$userId}");
            $this->view->render('user/change_password', 'Cambiar contraseña', [], Messages::UPDATE_OK);
        } else {
            $this->view->render('user/change_password', 'Cambiar contraseña', [], Messages::UPDATE_ERROR);
        }
    }

    public function deleteAccount(): void
    {
        $this->view->render('user/delete_account', 'Borrar cuenta');
    }

    public function deleteAccountPost(): void
    {
        $password = $this->input('Password');
        $validator = (new Validator())->required('Password', $password)->length('Password', $password, 0, 50);
        if (!$validator->isValid()) {
            $this->view->render('user/delete_account', 'Borrar cuenta', ['errors' => $validator->errors()]);
            return;
        }

        if (!$this->verifyCurrentPassword($password)) {
            $this->view->render('user/delete_account', 'Borrar cuenta', [], Messages::WRONG_PASSWORD);
            return;
        }

        $userId = $this->auth->userId();
        if ($this->users->deleteAccount($userId) === RepositoryResult::Success) {
            $this->auth->signOut();
            $this->logger->info("Cuenta eliminada por el usuario {$userId}");
            Flash::set(Messages::ACCOUNT_DELETED);
            $this->redirect('/User/Index');
            return;
        }

        $this->view->render('user/delete_account', 'Borrar cuenta', [], Messages::ACCOUNT_DELETE_ERROR);
    }

    public function closeSession(): void
    {
        $this->auth->signOut();
        $this->redirect('/User/Index');
    }

    /** true si la petición fue rechazada por el límite por IP (respuesta 429 ya emitida). */
    private function rejectIfRateLimited(): bool
    {
        if (!$this->rateLimiter->isRejected($this->request->clientIp())) {
            return false;
        }
        http_response_code(429);
        header('Content-Type: text/plain; charset=utf-8');
        echo Messages::TOO_MANY_REQUESTS;
        return true;
    }

    private function sendConfirmationEmail(string $email, int $userId): void
    {
        $token = $this->tokens->createEmailConfirmationToken($userId);
        $link = $this->request->absoluteUrl('/User/ConfirmEmail?token=' . urlencode($token));
        $this->email->send(
            $email,
            'Confirma tu cuenta',
            "Para confirmar tu cuenta visita: <a href=\"{$link}\">{$link}</a>"
        );
    }

    private function verifyCurrentPassword(string $password): bool
    {
        $user = $this->users->getUser($this->auth->userId());
        return $user !== null
            && $this->passwords->verify($user->passwordHash, $password) !== PasswordVerificationOutcome::Failed;
    }

    private function refreshAndConfirm(bool $logEmailChange = false): string
    {
        $this->auth->refreshClaims($this->auth->userId());
        if ($logEmailChange) {
            $this->logger->info("Correo actualizado para el usuario {$this->auth->userId()}");
        }
        return Messages::UPDATE_OK;
    }
}
