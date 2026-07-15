<?php
/** Variables: string $email; opcionales: array $errors, ?string $message. */
use Encuestas\Web\Csrf;

$errors = $errors ?? [];
?>
<div class="row justify-content-center mt-3">
    <div class="col-md-8 col-lg-6">
        <?php require __DIR__ . '/../shared/message.php'; ?>
        <h4 class="text-center mb-3"><?= e($title) ?></h4>
        <div class="card">
            <div class="card-body">
                <form action="/User/ChangeEmail" method="post">
                    <?= Csrf::field() ?>
                    <div class="mb-3">
                        <label class="form-label" for="Email">Correo electrónico</label>
                        <input class="form-control" type="email" id="Email" name="Email" maxlength="50" required
                               autocomplete="email" value="<?= e($email) ?>">
                        <span class="text-danger small"><?= e($errors['Email'] ?? '') ?></span>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="Password">Contraseña</label>
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
