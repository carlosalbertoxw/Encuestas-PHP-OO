<?php

declare(strict_types=1);

namespace Encuestas\Web\Controllers;

use Encuestas\Data\PollRepository;
use Encuestas\Data\UserRepository;
use Encuestas\Web\Flash;
use Encuestas\Web\NotFoundException;
use Encuestas\Web\RequestContext;
use Encuestas\Web\Services\AccountService;
use Encuestas\Web\Services\AuthService;
use Encuestas\Web\Services\LoginResult;
use Encuestas\Web\Services\Messages;
use Encuestas\Web\Services\RateLimiter;
use Encuestas\Web\Services\ResetPasswordResult;
use Encuestas\Web\Validator;
use Encuestas\Web\View;

/**
 * Página de inicio, perfiles públicos y flujos de acceso: inicio de sesión,
 * registro, confirmación de correo, recuperación de contraseña y cierre de
 * sesión. La gestión de la cuenta autenticada vive en AccountController.
 */
final class UserController extends Controller
{
    public function __construct(
        View $view,
        AuthService $auth,
        RequestContext $request,
        private readonly UserRepository $users,
        private readonly PollRepository $polls,
        private readonly AccountService $accounts,
        private readonly RateLimiter $rateLimiter,
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
            'result' => $this->polls->getPolls($profile->user->id, $this->pageNumber()),
            'profileUserName' => $profile->userName,
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

        $validator = (new Validator())
            ->required('Email', $email)->email('Email', $email)->length('Email', $email, 0, Validator::EMAIL_MAX)
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
            ->required('Email', $email)->email('Email', $email)->length('Email', $email, 0, Validator::EMAIL_MAX)
            ->required('Password', $password)->length('Password', $password, 6, 50)
            ->required('RePassword', $rePassword)->match('RePassword', $rePassword, $password);
        if (!$validator->isValid()) {
            $this->view->render('user/index', 'Inicio', [
                'registerErrors' => $validator->errors(),
                'registerEmail' => $email,
            ]);
            return;
        }

        $this->accounts->register($email, $password);
        // Se responde lo mismo exista o no el correo, para no revelar cuentas.
        $this->view->render('user/index', 'Inicio', [], Messages::REGISTER_ACKNOWLEDGED);
    }

    public function confirmEmail(): void
    {
        $token = $this->query('token');
        Flash::set($token !== null && $this->accounts->confirmEmail($token)
            ? Messages::EMAIL_CONFIRMED
            : Messages::EMAIL_CONFIRMATION_INVALID);
        $this->redirect('/User/Index');
    }

    public function resendConfirmation(): void
    {
        $this->view->render('user/resend_confirmation', 'Reenviar confirmación');
    }

    public function resendConfirmationPost(): void
    {
        if ($this->rejectIfRateLimited()) {
            return;
        }

        $email = $this->input('Email');
        $validator = (new Validator())
            ->required('Email', $email)->email('Email', $email)->length('Email', $email, 0, Validator::EMAIL_MAX);
        if (!$validator->isValid()) {
            $this->view->render('user/resend_confirmation', 'Reenviar confirmación', [
                'errors' => $validator->errors(),
                'email' => $email,
            ]);
            return;
        }

        $this->accounts->resendConfirmation($email);
        // Mensaje genérico para no revelar si el correo existe ni si ya estaba confirmado.
        $this->view->render('user/resend_confirmation', 'Reenviar confirmación', [], Messages::CONFIRMATION_RESENT);
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
            ->required('Email', $email)->email('Email', $email)->length('Email', $email, 0, Validator::EMAIL_MAX);
        if (!$validator->isValid()) {
            $this->view->render('user/forgot_password', 'Recuperar contraseña', [
                'errors' => $validator->errors(),
                'email' => $email,
            ]);
            return;
        }

        $this->accounts->requestPasswordReset($email);
        // Mensaje genérico para no revelar si el correo existe.
        $this->view->render('user/forgot_password', 'Recuperar contraseña', [], Messages::PASSWORD_RESET_SENT);
    }

    public function resetPassword(): void
    {
        $token = $this->query('token');
        if ($token === null || !$this->accounts->isPasswordResetTokenValid($token)) {
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
        if ($this->rejectIfRateLimited()) {
            return;
        }

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

        $result = $this->accounts->resetPassword($token, $newPassword);
        if ($result === ResetPasswordResult::Success) {
            Flash::set(Messages::PASSWORD_RESET_OK);
            $this->redirect('/User/Index');
            return;
        }

        $this->view->render(
            'user/reset_password',
            'Restablecer contraseña',
            ['token' => $token],
            $result === ResetPasswordResult::InvalidToken ? Messages::PASSWORD_RESET_INVALID : Messages::UPDATE_ERROR
        );
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
        $this->tooManyRequests();
        return true;
    }
}
