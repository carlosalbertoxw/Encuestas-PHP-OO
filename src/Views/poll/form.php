<?php
/** Alta/edición de encuesta. Variables: Encuestas\Model\Poll $poll, string $title; opcionales: array $errors, ?string $message. */
use Encuestas\Web\Csrf;

$errors = $errors ?? [];
?>
<div class="row justify-content-center mt-3">
    <div class="col-md-8 col-lg-6">
        <?php require __DIR__ . '/../shared/message.php'; ?>
        <h4 class="text-center mb-3"><?= e($title) ?></h4>
        <div class="card">
            <div class="card-body">
                <form action="<?= $poll->id === 0 ? '/Poll/Add' : '/Poll/Update' ?>" method="post">
                    <?= Csrf::field() ?>
                    <input type="hidden" name="Id" value="<?= e($poll->id) ?>">
                    <div class="mb-3">
                        <label class="form-label" for="Title">Título</label>
                        <input class="form-control" id="Title" name="Title" maxlength="250" required value="<?= e($poll->title) ?>">
                        <span class="text-danger small"><?= e($errors['Title'] ?? '') ?></span>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="Description">Descripción</label>
                        <textarea class="form-control" rows="3" id="Description" name="Description" maxlength="500" required><?= e($poll->description) ?></textarea>
                        <span class="text-danger small"><?= e($errors['Description'] ?? '') ?></span>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="Position">Posición</label>
                        <input class="form-control" type="number" min="1" max="999999" id="Position" name="Position" required
                               value="<?= e($poll->position) ?>">
                        <span class="text-danger small"><?= e($errors['Position'] ?? '') ?></span>
                    </div>
                    <button class="btn btn-primary w-100" type="submit">Guardar</button>
                </form>
            </div>
        </div>
    </div>
</div>
