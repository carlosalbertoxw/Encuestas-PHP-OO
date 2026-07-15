<?php
/**
 * Layout compartido.
 * Variables: string $title, string $content, Encuestas\Web\View $view,
 * Encuestas\Web\Services\AuthService $auth.
 */
use Encuestas\Web\Csrf;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?> - Encuestas</title>
    <link rel="icon" href="/favicon.ico">
    <link rel="stylesheet" href="<?= e($view->asset('/css/bootstrap.min.css')) ?>">
    <link rel="stylesheet" href="<?= e($view->asset('/css/site.css')) ?>">
</head>
<body>
    <nav class="navbar navbar-expand-lg fixed-top bg-primary" data-bs-theme="dark">
        <div class="container">
            <?php if ($auth->isAuthenticated()): ?>
                <a class="navbar-brand" href="/Poll/Index">Encuestas</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar"
                        aria-controls="navbar" aria-expanded="false" aria-label="Alternar navegación">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div id="navbar" class="collapse navbar-collapse">
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item"><a class="nav-link" href="/Poll/Add">Agregar encuesta</a></li>
                    </ul>
                    <ul class="navbar-nav">
                        <li class="nav-item dropdown">
                            <a href="#" class="nav-link dropdown-toggle" role="button" data-bs-toggle="dropdown" aria-expanded="false">Usuario</a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="/User/Index?profile=<?= e(urlencode($auth->userName())) ?>">Ver perfil</a></li>
                                <li><a class="dropdown-item" href="/User/EditProfile">Editar perfil</a></li>
                                <li><a class="dropdown-item" href="/User/ChangeUser">Cambiar usuario</a></li>
                                <li><a class="dropdown-item" href="/User/ChangeEmail">Cambiar correo electrónico</a></li>
                                <li><a class="dropdown-item" href="/User/ChangePassword">Cambiar contraseña</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="/User/DeleteAccount">Borrar cuenta</a></li>
                                <li>
                                    <form action="/User/CloseSession" method="post">
                                        <?= Csrf::field() ?>
                                        <button type="submit" class="dropdown-item">Cerrar sesión</button>
                                    </form>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </div>
            <?php else: ?>
                <a class="navbar-brand" href="/User/Index">Encuestas</a>
            <?php endif; ?>
        </div>
    </nav>
    <main class="container body-content">
        <?= $content ?>
    </main>
    <script src="<?= e($view->asset('/js/bootstrap.bundle.min.js')) ?>"></script>
    <script src="<?= e($view->asset('/js/site.js')) ?>"></script>
</body>
</html>
