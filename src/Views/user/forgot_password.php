<?php
/** Variables opcionales: array $errors, string $email, ?string $message. */
use Encuestas\Web\Csrf;

$errors = $errors ?? [];
$email = $email ?? '';
?>
<div class="row justify-content-center mt-3">
    <div class="col-md-8 col-lg-6">
        <?php require __DIR__ . '/../shared/message.php'; ?>
        <h4 class="text-center mb-3"><?= e($title) ?></h4>
        <div class="card">
            <div class="card-body">
                <p class="text-secondary">Ingresa tu correo y te enviaremos un enlace para restablecer la contraseña.</p>
                <form action="/User/ForgotPassword" method="post">
                    <?= Csrf::field() ?>
                    <div class="mb-3">
                        <label class="form-label" for="Email">Correo electrónico</label>
                        <input class="form-control" type="email" id="Email" name="Email" maxlength="50" required
                               autocomplete="email" value="<?= e($email) ?>">
                        <span class="text-danger small"><?= e($errors['Email'] ?? '') ?></span>
                    </div>
                    <button class="btn btn-primary w-100" type="submit">Enviar enlace</button>
                </form>
            </div>
        </div>
    </div>
</div>
