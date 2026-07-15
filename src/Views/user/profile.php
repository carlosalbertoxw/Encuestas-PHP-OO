<?php
/** Perfil público. Variables: list<Encuestas\Model\Poll> $polls, string $title, ?string $message. */
?>
<div class="mt-3">
    <?php require __DIR__ . '/../shared/message.php'; ?>
    <h4 class="text-center mb-3"><?= e($title) ?></h4>
    <?php if ($polls === []): ?>
        <p class="text-center text-secondary">Este usuario aún no tiene encuestas.</p>
    <?php endif; ?>
    <?php foreach ($polls as $item): ?>
        <div class="card mb-3">
            <div class="card-body">
                <h5 class="card-title"><?= e($item->title) ?></h5>
                <p class="card-text"><?= e($item->description) ?></p>
            </div>
            <div class="card-footer">
                <a class="btn btn-sm btn-outline-primary" href="/Answer/Add/<?= e($item->id) ?>">Responder</a>
            </div>
        </div>
    <?php endforeach; ?>
</div>
