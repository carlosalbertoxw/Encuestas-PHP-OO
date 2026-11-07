<?php
/**
 * Paginación compartida.
 * Variables: Encuestas\Data\PagedResult $result; string $pagerBase, la URL del listado
 * terminada en '?' o '&' (se le agrega page=N).
 */
?>
<?php if ($result->totalPages() > 1): ?>
    <nav aria-label="Paginación">
        <ul class="pagination justify-content-center">
            <li class="page-item <?= $result->hasPrevious() ? '' : 'disabled' ?>">
                <a class="page-link" href="<?= e($pagerBase . 'page=' . ($result->page - 1)) ?>">Anterior</a>
            </li>
            <li class="page-item disabled">
                <span class="page-link"><?= e($result->page) ?> / <?= e($result->totalPages()) ?></span>
            </li>
            <li class="page-item <?= $result->hasNext() ? '' : 'disabled' ?>">
                <a class="page-link" href="<?= e($pagerBase . 'page=' . ($result->page + 1)) ?>">Siguiente</a>
            </li>
        </ul>
    </nav>
<?php endif; ?>
