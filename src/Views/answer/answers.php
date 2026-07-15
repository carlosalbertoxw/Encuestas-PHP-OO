<?php
/**
 * Respuestas recibidas, paginadas.
 * Variables: Encuestas\Data\PagedResult $result, int $pollId, string $title, ?string $message.
 */
?>
<div class="mt-3">
    <?php require __DIR__ . '/../shared/message.php'; ?>
    <h4 class="text-center mb-1"><?= e($title) ?></h4>
    <p class="text-center text-secondary"><?= e($result->totalCount) ?> respuesta(s)</p>

    <?php if ($result->totalCount === 0): ?>
        <p class="text-center text-secondary">Esta encuesta aún no tiene respuestas.</p>
    <?php endif; ?>
    <?php foreach ($result->items as $item): ?>
        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <strong><?= e($item->userName) ?></strong>
                    <span class="stars" title="<?= e($item->stars) ?> de 5 estrellas">
                        <?php for ($i = 1; $i <= 5; $i++): ?><?= $i <= $item->stars ? '★' : '☆' ?><?php endfor; ?>
                    </span>
                </div>
                <?php if ($item->comment !== ''): ?>
                    <p class="card-text mt-2 mb-0"><?= e($item->comment) ?></p>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>

    <?php if ($result->totalPages() > 1): ?>
        <nav aria-label="Paginación de respuestas">
            <ul class="pagination justify-content-center">
                <li class="page-item <?= $result->hasPrevious() ? '' : 'disabled' ?>">
                    <a class="page-link" href="/Answer/Answers/<?= e($pollId) ?>?page=<?= e($result->page - 1) ?>">Anterior</a>
                </li>
                <li class="page-item disabled">
                    <span class="page-link"><?= e($result->page) ?> / <?= e($result->totalPages()) ?></span>
                </li>
                <li class="page-item <?= $result->hasNext() ? '' : 'disabled' ?>">
                    <a class="page-link" href="/Answer/Answers/<?= e($pollId) ?>?page=<?= e($result->page + 1) ?>">Siguiente</a>
                </li>
            </ul>
        </nav>
    <?php endif; ?>
</div>
