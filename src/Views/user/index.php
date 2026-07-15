<?php
/**
 * Página de inicio: acceso y registro.
 * Variables opcionales: array $loginErrors, string $loginEmail,
 * array $registerErrors, string $registerEmail, ?string $message.
 */
use Encuestas\Web\Csrf;

$loginErrors = $loginErrors ?? [];
$loginEmail = $loginEmail ?? '';
$registerErrors = $registerErrors ?? [];
$registerEmail = $registerEmail ?? '';
?>
<div class="row mt-3 g-4">
    <div class="col-12">
        <?php require __DIR__ . '/../shared/message.php'; ?>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title text-center mb-3">Acceso</h4>
                <form action="/User/Login" method="post">
                    <?= Csrf::field() ?>
                    <div class="mb-3">
                        <label class="form-label" for="LoginEmail">Correo electrónico</label>
                        <input class="form-control" type="email" id="LoginEmail" name="Email" maxlength="50" required
                               autocomplete="username" value="<?= e($loginEmail) ?>">
                        <span class="text-danger small"><?= e($loginErrors['Email'] ?? '') ?></span>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="LoginPassword">Contraseña</label>
                        <input class="form-control" type="password" id="LoginPassword" name="Password" maxlength="50" required
                               autocomplete="current-password">
                        <span class="text-danger small"><?= e($loginErrors['Password'] ?? '') ?></span>
                    </div>
                    <button class="btn btn-primary w-100" type="submit">Entrar</button>
                    <div class="text-center mt-2">
                        <a href="/User/ForgotPassword">¿Olvidaste tu contraseña?</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title text-center mb-3">Registro</h4>
                <form action="/User/Register" method="post">
                    <?= Csrf::field() ?>
                    <div class="mb-3">
                        <label class="form-label" for="RegisterEmail">Correo electrónico</label>
                        <input class="form-control" type="email" id="RegisterEmail" name="Email" maxlength="50" required
                               autocomplete="email" value="<?= e($registerEmail) ?>">
                        <span class="text-danger small"><?= e($registerErrors['Email'] ?? '') ?></span>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="RegisterPassword">Contraseña</label>
                        <input class="form-control" type="password" id="RegisterPassword" name="Password"
                               minlength="6" maxlength="50" required autocomplete="new-password">
                        <span class="text-danger small"><?= e($registerErrors['Password'] ?? '') ?></span>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="RegisterRePassword">Repita contraseña</label>
                        <input class="form-control" type="password" id="RegisterRePassword" name="RePassword"
                               minlength="6" maxlength="50" required autocomplete="new-password"
                               data-match="RegisterPassword" data-match-message="Las contraseñas no coinciden">
                        <span class="text-danger small"><?= e($registerErrors['RePassword'] ?? '') ?></span>
                    </div>
                    <button class="btn btn-primary w-100" type="submit">Registrarse</button>
                </form>
            </div>
        </div>
    </div>
</div>
