<?php
/** Alerta informativa descartable. Variable: ?string $message. */
if (!empty($message)): ?>
    <div class="alert alert-info alert-dismissible fade show text-center" role="alert">
        <?= e($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
    </div>
<?php endif; ?>
