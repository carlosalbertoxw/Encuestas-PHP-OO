<?php
/** Variables opcionales: array $errors, ?string $message. */
use Encuestas\Web\Csrf;

$errors = $errors ?? [];
?>
<div class="row justify-content-center mt-3">
    <div class="col-md-8 col-lg-6">
        <?php require __DIR__ . '/../shared/message.php'; ?>
        <h4 class="text-center mb-3"><?= e($title) ?></h4>
        <div class="card">
            <div class="card-body">
                <form action="/User/ChangePassword" method="post">
                    <?= Csrf::field() ?>
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
                    <div class="mb-3">
                        <label class="form-label" for="Password">Contraseña actual</label>
                        <input class="form-control" type="password" id="Password" name="Password" maxlength="50" required
                               autocomplete="current-password">
                        <span class="text-danger small"><?= e($errors['Password'] ?? '') ?></span>
                    </div>
                    <button class="btn btn-primary w-100" type="submit">Guardar</button>
                </form>
            </div>
        </div>
    </div>
</div>
