<?php
/** Variables opcionales: array $errors, ?string $message. */
use Encuestas\Web\Csrf;

$errors = $errors ?? [];
?>
<div class="row justify-content-center mt-3">
    <div class="col-md-8 col-lg-6">
        <?php require __DIR__ . '/../shared/message.php'; ?>
        <h4 class="text-center mb-3"><?= e($title) ?></h4>
        <div class="card border-danger">
            <div class="card-body">
                <p class="text-danger">Esta acción elimina tu cuenta y todos tus datos de forma permanente.</p>
                <form action="/User/DeleteAccount" method="post"
                      data-confirm="¿Seguro que quieres borrar tu cuenta? Esta acción no se puede deshacer.">
                    <?= Csrf::field() ?>
                    <div class="mb-3">
                        <label class="form-label" for="Password">Contraseña</label>
                        <input class="form-control" type="password" id="Password" name="Password" maxlength="50" required
                               autocomplete="current-password">
                        <span class="text-danger small"><?= e($errors['Password'] ?? '') ?></span>
                    </div>
                    <button class="btn btn-danger w-100" type="submit">Borrar mi cuenta</button>
                </form>
            </div>
        </div>
    </div>
</div>
