<?php
/** Tablero. Variables: list<Encuestas\Model\Poll> $polls, string $title, ?string $message. */
use Encuestas\Web\Csrf;
?>
<div class="mt-3">
    <?php require __DIR__ . '/../shared/message.php'; ?>
    <h4 class="text-center mb-3"><?= e($title) ?></h4>
    <?php if ($polls === []): ?>
        <p class="text-center text-secondary">Aún no tienes encuestas. Usa «Agregar encuesta» para crear la primera.</p>
    <?php endif; ?>
    <?php foreach ($polls as $item): ?>
        <div class="card mb-3">
            <div class="card-body">
                <h5 class="card-title"><?= e($item->title) ?></h5>
                <p class="card-text"><?= e($item->description) ?></p>
            </div>
            <div class="card-footer d-flex flex-wrap gap-2">
                <a class="btn btn-sm btn-outline-primary" href="/Poll/Edit/<?= e($item->id) ?>">Editar</a>
                <a class="btn btn-sm btn-outline-secondary" href="/Answer/Answers/<?= e($item->id) ?>">Ver respuestas</a>
                <a class="btn btn-sm btn-outline-secondary" href="/Answer/Add/<?= e($item->id) ?>">Responder</a>
                <form action="/Poll/Delete/<?= e($item->id) ?>" method="post" class="ms-auto"
                      data-confirm="¿Estás seguro de borrar este registro?">
                    <?= Csrf::field() ?>
                    <button type="submit" class="btn btn-sm btn-outline-danger">Borrar</button>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
</div>
