<?php
/** Responder encuesta. Variables: Encuestas\Model\Poll $poll, string $title, ?string $message. */
use Encuestas\Web\Csrf;
?>
<div class="row justify-content-center mt-3">
    <div class="col-md-8 col-lg-6">
        <?php require __DIR__ . '/../shared/message.php'; ?>
        <h4 class="text-center mb-3"><?= e($title) ?></h4>
        <div class="card mb-3">
            <div class="card-body">
                <h5 class="card-title"><?= e($poll->title) ?></h5>
                <p class="card-text"><?= e($poll->description) ?></p>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <form action="/Answer/Add/<?= e($poll->id) ?>" method="post">
                    <?= Csrf::field() ?>
                    <div class="mb-3">
                        <span class="form-label d-block">Estrellas</span>
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="Stars" id="stars<?= $i ?>" value="<?= $i ?>" required>
                                <label class="form-check-label" for="stars<?= $i ?>"><?= $i ?></label>
                            </div>
                        <?php endfor; ?>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="Comment">Comentario</label>
                        <textarea class="form-control" rows="3" name="Comment" id="Comment" maxlength="1000"></textarea>
                    </div>
                    <button class="btn btn-primary w-100" type="submit">Enviar respuesta</button>
                </form>
            </div>
        </div>
    </div>
</div>
