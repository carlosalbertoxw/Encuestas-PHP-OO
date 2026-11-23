<?php

declare(strict_types=1);

namespace Encuestas\Web\Controllers;

use Encuestas\Data\RepositoryResult;
use Encuestas\Data\UserRepository;
use Encuestas\Web\Flash;
use Encuestas\Web\RequestContext;
use Encuestas\Web\Services\AccountService;
use Encuestas\Web\Services\AuthService;
use Encuestas\Web\Services\Messages;
use Encuestas\Web\Services\RateLimiter;
use Encuestas\Web\Validator;
use Encuestas\Web\View;

/** Gestión de la cuenta autenticada: perfil, usuario, correo, contraseña y borrado. */
final class AccountController extends Controller
{
    public function __construct(
        View $view,
        AuthService $auth,
        RequestContext $request,
        private readonly UserRepository $users,
        private readonly AccountService $accounts,
        private readonly RateLimiter $emailChangeLimiter,
    ) {
        parent::__construct($view, $auth, $request);
    }

    public function editProfile(): void
    {
        $this->view->render('account/edit_profile', 'Editar perfil', ['name' => $this->auth->name()]);
    }

    public function editProfilePost(): void
    {
        $name = $this->input('Name');
        $validator = (new Validator())->required('Name', $name)->length('Name', $name, 0, 50);
        if (!$validator->isValid()) {
            $this->view->render('account/edit_profile', 'Editar perfil', ['name' => $name, 'errors' => $validator->errors()]);
            return;
        }

        $message = $this->users->updateName($this->auth->userId(), $name) === RepositoryResult::Success
            ? $this->refreshClaims()
            : Messages::UPDATE_ERROR;
        $this->view->render('account/edit_profile', 'Editar perfil', ['name' => $name], $message);
    }

    public function changeUser(): void
    {
        $this->view->render('account/change_user', 'Cambiar usuario', ['userName' => $this->auth->userName()]);
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
            $this->view->render('account/change_user', 'Cambiar usuario', [
                'userName' => $userName,
                'errors' => $validator->errors(),
            ]);
            return;
        }

        if (!$this->accounts->verifyPassword($this->auth->userId(), $password)) {
            $this->view->render('account/change_user', 'Cambiar usuario', ['userName' => $userName], Messages::WRONG_PASSWORD);
            return;
        }

        $message = match ($this->users->updateUserName($this->auth->userId(), $userName)) {
            RepositoryResult::Success => $this->refreshClaims(),
            RepositoryResult::Duplicate => Messages::USER_NAME_TAKEN,
            default => Messages::UPDATE_ERROR,
        };
        $this->view->render('account/change_user', 'Cambiar usuario', ['userName' => $userName], $message);
    }

    public function changeEmail(): void
    {
        $this->view->render('account/change_email', 'Cambiar correo', ['email' => $this->auth->email()]);
    }

    /**
     * Solicita el cambio de correo: el nuevo queda pendiente y solo se aplica
     * cuando se abre el enlace enviado a esa dirección. Limitado por usuario,
     * porque cada solicitud envía correos.
     */
    public function changeEmailPost(): void
    {
        if ($this->emailChangeLimiter->isRejected((string) $this->auth->userId())) {
            $this->tooManyRequests();
            return;
        }

        $email = $this->input('Email');
        $password = $this->input('Password');

        $validator = (new Validator())
            ->required('Email', $email)->email('Email', $email)->length('Email', $email, 0, Validator::EMAIL_MAX)
            ->required('Password', $password)->length('Password', $password, 0, 50);
        if (!$validator->isValid()) {
            $this->view->render('account/change_email', 'Cambiar correo', ['email' => $email, 'errors' => $validator->errors()]);
            return;
        }

        if (!$this->accounts->verifyPassword($this->auth->userId(), $password)) {
            $this->view->render('account/change_email', 'Cambiar correo', ['email' => $email], Messages::WRONG_PASSWORD);
            return;
        }

        $currentEmail = $this->auth->email();
        if (strcasecmp($email, $currentEmail) === 0) {
            $this->view->render('account/change_email', 'Cambiar correo', ['email' => $email], Messages::SAME_EMAIL);
            return;
        }

        $this->accounts->requestEmailChange($this->auth->userId(), $currentEmail, $email);
        $this->view->render(
            'account/change_email',
            'Cambiar correo',
            ['email' => $currentEmail],
            Messages::EMAIL_CHANGE_REQUESTED
        );
    }

    /**
     * Aplica el correo pendiente. Rota el sello de seguridad, así que todas las
     * sesiones (incluida la actual) se cierran y hay que entrar de nuevo.
     */
    public function confirmEmailChange(): void
    {
        $token = $this->query('token');
        $result = $token === null ? RepositoryResult::NotFound : $this->accounts->confirmEmailChange($token);
        if ($result === RepositoryResult::Success && $this->auth->isAuthenticated()) {
            $this->auth->signOut();
        }

        Flash::set(match ($result) {
            RepositoryResult::Success => Messages::EMAIL_CHANGED,
            RepositoryResult::Duplicate => Messages::EMAIL_IN_USE,
            default => Messages::EMAIL_CHANGE_INVALID,
        });
        $this->redirect('/User/Index');
    }

    public function changePassword(): void
    {
        $this->view->render('account/change_password', 'Cambiar contraseña');
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
            $this->view->render('account/change_password', 'Cambiar contraseña', ['errors' => $validator->errors()]);
            return;
        }

        $userId = $this->auth->userId();
        if (!$this->accounts->verifyPassword($userId, $password)) {
            $this->view->render('account/change_password', 'Cambiar contraseña', [], Messages::WRONG_PASSWORD);
            return;
        }

        // El sello rota y cierra las otras sesiones; la actual se recarga con el sello nuevo.
        $message = $this->accounts->changePassword($userId, $newPassword)
            ? $this->refreshClaims()
            : Messages::UPDATE_ERROR;
        $this->view->render('account/change_password', 'Cambiar contraseña', [], $message);
    }

    public function deleteAccount(): void
    {
        $this->view->render('account/delete_account', 'Borrar cuenta');
    }

    public function deleteAccountPost(): void
    {
        $password = $this->input('Password');
        $validator = (new Validator())->required('Password', $password)->length('Password', $password, 0, 50);
        if (!$validator->isValid()) {
            $this->view->render('account/delete_account', 'Borrar cuenta', ['errors' => $validator->errors()]);
            return;
        }

        $userId = $this->auth->userId();
        if (!$this->accounts->verifyPassword($userId, $password)) {
            $this->view->render('account/delete_account', 'Borrar cuenta', [], Messages::WRONG_PASSWORD);
            return;
        }

        if ($this->accounts->deleteAccount($userId)) {
            $this->auth->signOut();
            Flash::set(Messages::ACCOUNT_DELETED);
            $this->redirect('/User/Index');
            return;
        }

        $this->view->render('account/delete_account', 'Borrar cuenta', [], Messages::ACCOUNT_DELETE_ERROR);
    }

    private function refreshClaims(): string
    {
        $this->auth->refreshClaims($this->auth->userId());
        return Messages::UPDATE_OK;
    }
}
