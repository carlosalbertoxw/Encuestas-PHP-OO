<?php
/** Variables: string $name; opcionales: array $errors, ?string $message. */
use Encuestas\Web\Csrf;

$errors = $errors ?? [];
?>
<div class="row justify-content-center mt-3">
    <div class="col-md-8 col-lg-6">
        <?php require __DIR__ . '/../shared/message.php'; ?>
        <h4 class="text-center mb-3"><?= e($title) ?></h4>
        <div class="card">
            <div class="card-body">
                <form action="/User/EditProfile" method="post">
                    <?= Csrf::field() ?>
                    <div class="mb-3">
                        <label class="form-label" for="Name">Nombre</label>
                        <input class="form-control" id="Name" name="Name" maxlength="50" required value="<?= e($name) ?>">
                        <span class="text-danger small"><?= e($errors['Name'] ?? '') ?></span>
                    </div>
                    <button class="btn btn-primary w-100" type="submit">Guardar</button>
                </form>
            </div>
        </div>
    </div>
</div>
