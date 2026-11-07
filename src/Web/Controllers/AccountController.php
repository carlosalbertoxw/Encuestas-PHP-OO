<?php

declare(strict_types=1);

namespace Encuestas\Web\Controllers;

use Encuestas\Config;
use Encuestas\Data\RepositoryResult;
use Encuestas\Data\UserRepository;
use Encuestas\Infrastructure\Logger;
use Encuestas\Web\Flash;
use Encuestas\Web\RequestContext;
use Encuestas\Web\Services\AuthService;
use Encuestas\Web\Services\EmailSender;
use Encuestas\Web\Services\EmailTemplates;
use Encuestas\Web\Services\Messages;
use Encuestas\Web\Services\PasswordService;
use Encuestas\Web\Services\PasswordVerificationOutcome;
use Encuestas\Web\Services\TokenService;
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
        private readonly PasswordService $passwords,
        private readonly TokenService $tokens,
        private readonly EmailSender $email,
        private readonly Logger $logger,
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

        if (!$this->verifyCurrentPassword($password)) {
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
     * cuando se abre el enlace enviado a esa dirección. Se avisa además al
     * correo actual.
     */
    public function changeEmailPost(): void
    {
        $email = $this->input('Email');
        $password = $this->input('Password');

        $validator = (new Validator())
            ->required('Email', $email)->email('Email', $email)->length('Email', $email, 0, Validator::EMAIL_MAX)
            ->required('Password', $password)->length('Password', $password, 0, 50);
        if (!$validator->isValid()) {
            $this->view->render('account/change_email', 'Cambiar correo', ['email' => $email, 'errors' => $validator->errors()]);
            return;
        }

        if (!$this->verifyCurrentPassword($password)) {
            $this->view->render('account/change_email', 'Cambiar correo', ['email' => $email], Messages::WRONG_PASSWORD);
            return;
        }

        $currentEmail = $this->auth->email();
        if (strcasecmp($email, $currentEmail) === 0) {
            $this->view->render('account/change_email', 'Cambiar correo', ['email' => $email], Messages::SAME_EMAIL);
            return;
        }

        $userId = $this->auth->userId();
        if ($this->users->getProfileByEmail($email) !== null) {
            // No se revela en pantalla que el correo tiene cuenta: solo lo sabe quien controla ese buzón.
            $this->email->send($email, 'Intento de usar tu correo', EmailTemplates::text(
                'Alguien intentó cambiar el correo de otra cuenta de Encuestas a esta dirección, pero ya tienes '
                . 'una cuenta con ella. No es necesario que hagas nada.'
            ));
        } elseif ($this->users->setPendingEmail($userId, $email) === RepositoryResult::Success) {
            $token = $this->tokens->createEmailChangeToken($userId, $email);
            $link = Config::absoluteUrl('/Account/ConfirmEmailChange?token=' . urlencode($token));
            $this->email->send(
                $email,
                'Confirma tu nuevo correo',
                EmailTemplates::link('Para usar esta dirección en tu cuenta de Encuestas visita:', $link)
            );
            $this->email->send($currentEmail, 'Solicitud de cambio de correo', EmailTemplates::text(
                'Se solicitó cambiar el correo de tu cuenta de Encuestas. Si no fuiste tú, cambia tu contraseña: '
                . 'el cambio no se aplicará mientras nadie abra el enlace enviado al correo nuevo.'
            ));
            $this->logger->info('Cambio de correo solicitado', ['userId' => $userId]);
        }

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
        $change = $token === null ? null : $this->tokens->validateEmailChangeToken($token);
        $result = $change === null
            ? RepositoryResult::NotFound
            : $this->users->confirmEmailChange($change['userId'], $change['email'], AuthService::newSecurityStamp());

        if ($change !== null && $result === RepositoryResult::Success) {
            if ($this->auth->isAuthenticated()) {
                $this->auth->signOut();
            }
            $this->logger->info('Correo actualizado', ['userId' => $change['userId']]);
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

        if (!$this->verifyCurrentPassword($password)) {
            $this->view->render('account/change_password', 'Cambiar contraseña', [], Messages::WRONG_PASSWORD);
            return;
        }

        // Rota el sello de seguridad para cerrar otras sesiones; luego recarga la
        // sesión actual con el sello nuevo para no expulsar al propio usuario.
        $userId = $this->auth->userId();
        $result = $this->users->changePassword($userId, $this->passwords->hash($newPassword), AuthService::newSecurityStamp());
        if ($result === RepositoryResult::Success) {
            $this->auth->refreshClaims($userId);
            $this->logger->info('Contraseña cambiada', ['userId' => $userId]);
            $this->view->render('account/change_password', 'Cambiar contraseña', [], Messages::UPDATE_OK);
        } else {
            $this->view->render('account/change_password', 'Cambiar contraseña', [], Messages::UPDATE_ERROR);
        }
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

        if (!$this->verifyCurrentPassword($password)) {
            $this->view->render('account/delete_account', 'Borrar cuenta', [], Messages::WRONG_PASSWORD);
            return;
        }

        $userId = $this->auth->userId();
        if ($this->users->deleteAccount($userId) === RepositoryResult::Success) {
            $this->auth->signOut();
            $this->logger->info('Cuenta eliminada', ['userId' => $userId]);
            Flash::set(Messages::ACCOUNT_DELETED);
            $this->redirect('/User/Index');
            return;
        }

        $this->view->render('account/delete_account', 'Borrar cuenta', [], Messages::ACCOUNT_DELETE_ERROR);
    }

    private function verifyCurrentPassword(string $password): bool
    {
        $user = $this->users->getUser($this->auth->userId());
        return $user !== null
            && $this->passwords->verify($user->passwordHash, $password) !== PasswordVerificationOutcome::Failed;
    }

    private function refreshClaims(): string
    {
        $this->auth->refreshClaims($this->auth->userId());
        return Messages::UPDATE_OK;
    }
}
