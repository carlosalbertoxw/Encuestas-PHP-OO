<?php
/** Variables: string $token; opcionales: array $errors, ?string $message. */
use Encuestas\Web\Csrf;

$errors = $errors ?? [];
?>
<div class="row justify-content-center mt-3">
    <div class="col-md-8 col-lg-6">
        <?php require __DIR__ . '/../shared/message.php'; ?>
        <h4 class="text-center mb-3"><?= e($title) ?></h4>
        <?php if ($token !== ''): ?>
            <div class="card">
                <div class="card-body">
                    <form action="/User/ResetPassword" method="post">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="Token" value="<?= e($token) ?>">
                        <div class="mb-3">
                            <label class="form-label" for="NewPassword">Nueva contraseña</label>
                            <input class="form-control" type="password" id="NewPassword" name="NewPassword"
                                   minlength="6" maxlength="50" required autocomplete="new-password">
                            <span class="text-danger small"><?= e($errors['NewPassword'] ?? '') ?></span>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="ReNewPassword">Repita nueva contraseña</label>
                            <input class="form-control" type="password" id="ReNewPassword" name="ReNewPassword"
                                   minlength="6" maxlength="50" required autocomplete="new-password"
                                   data-match="NewPassword" data-match-message="Las contraseñas no coinciden">
                            <span class="text-danger small"><?= e($errors['ReNewPassword'] ?? '') ?></span>
                        </div>
                        <button class="btn btn-primary w-100" type="submit">Restablecer contraseña</button>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <p class="text-center">
                <a href="/User/ForgotPassword">Solicitar un nuevo enlace</a>
            </p>
        <?php endif; ?>
    </div>
</div>
