<?php

declare(strict_types=1);

// Front controller: toda petición que no sea un archivo estático entra aquí
// (ver public/.htaccess para Apache). Aplica cabeceras de seguridad, sesión,
// protección CSRF y despacha a los controladores.

use Encuestas\Config;
use Encuestas\Data\AnswerRepository;
use Encuestas\Data\Database;
use Encuestas\Data\PollRepository;
use Encuestas\Data\UserRepository;
use Encuestas\Infrastructure\Logger;
use Encuestas\Web\Controllers\AccountController;
use Encuestas\Web\Controllers\AnswerController;
use Encuestas\Web\Controllers\ErrorController;
use Encuestas\Web\Controllers\PollController;
use Encuestas\Web\Controllers\UserController;
use Encuestas\Web\Csrf;
use Encuestas\Web\DbSessionHandler;
use Encuestas\Web\NotFoundException;
use Encuestas\Web\RequestContext;
use Encuestas\Web\Router;
use Encuestas\Web\Services\AccountLockout;
use Encuestas\Web\Services\AuthService;
use Encuestas\Web\Services\DbAttemptCounterStore;
use Encuestas\Web\Services\LoggingEmailSender;
use Encuestas\Web\Services\PasswordService;
use Encuestas\Web\Services\RateLimiter;
use Encuestas\Web\Services\SmtpEmailSender;
use Encuestas\Web\Services\TokenService;
use Encuestas\Web\View;

// Con el servidor embebido de PHP (php -S) los archivos existentes se sirven tal cual.
// Solo se aceptan rutas que, ya resueltas, sigan dentro de public/.
if (PHP_SAPI === 'cli-server') {
    $file = realpath(__DIR__ . parse_url((string) $_SERVER['REQUEST_URI'], PHP_URL_PATH));
    // nosemgrep: php.lang.security.injection.tainted-filename.tainted-filename -- ruta confinada a public/
    if ($file !== false && str_starts_with($file, __DIR__ . DIRECTORY_SEPARATOR) && is_file($file)) {
        return false;
    }
}

require dirname(__DIR__) . '/vendor/autoload.php';

$isDevelopment = Config::isDevelopment();
ini_set('display_errors', $isDevelopment ? '1' : '0');
error_reporting(E_ALL);

$path = (string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?? '/');
$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

// Identificador de la petición: viaja en la cabecera X-Request-Id y en cada línea de log.
$logger = new Logger(bin2hex(random_bytes(8)));
header('X-Request-Id: ' . $logger->requestId());

// Liveness: solo confirma que PHP responde (sin BD, para que una caída de MySQL
// no provoque reinicios en bucle).
if ($path === '/health/live') {
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Healthy';
    return;
}

$db = Database::fromEnvironment();

// Readiness: además comprueba la conectividad con la base de datos.
if ($path === '/health/ready') {
    header('Content-Type: text/plain; charset=utf-8');
    try {
        $db->connection()->query('SELECT 1');
        echo 'Healthy';
    } catch (Throwable) {
        http_response_code(503);
        echo 'Unhealthy';
    }
    return;
}

// Las cabeceras X-Forwarded-* solo se aceptan desde proxies declarados en TRUSTED_PROXIES.
$request = RequestContext::fromEnvironment();
$isHttps = $request->isHttps();

// Redirección http -> https, activable con FORCE_HTTPS=true cuando la app
// termina TLS (directamente o tras un proxy de confianza). Apagada en
// desarrollo y por defecto, para no romper el acceso local por http. El destino
// sale de APP_BASE_URL, no de la cabecera Host.
if (!$isDevelopment && !$isHttps && Config::get('FORCE_HTTPS') === 'true') {
    http_response_code(308);
    $host = (string) parse_url(Config::baseUrl(), PHP_URL_HOST);
    header('Location: https://' . $host . ($_SERVER['REQUEST_URI'] ?? '/'));
    return;
}

// --- Cabeceras de seguridad ---
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');
// Sin scripts en línea; 'unsafe-inline' en estilos sigue por los estilos que
// Bootstrap aplica dinámicamente a los menús desplegables.
header(
    "Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; "
    . "img-src 'self' data:; frame-ancestors 'none'"
);

if (!$isDevelopment && $isHttps) {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

// --- Sesión (almacenada en MySQL; ver DbSessionHandler) ---
$sessionLifetime = 8 * 3600;
// Solo caracteres alfanuméricos: PHP no admite puntos en el nombre de la sesión.
session_name('EncuestasAuth');
$sessionCookieParams = [
    'lifetime' => $sessionLifetime,
    'path' => '/',
    'httponly' => true,
    'samesite' => 'Lax',
    // En desarrollo la app corre sobre http; fuera de él la cookie solo viaja por HTTPS.
    'secure' => $isDevelopment ? $isHttps : true,
];
session_set_cookie_params($sessionCookieParams);
// Garantiza que el recolector de sesiones caducadas corra de vez en cuando.
ini_set('session.gc_probability', '1');
ini_set('session.gc_divisor', '100');
session_set_save_handler(new DbSessionHandler($db, $sessionLifetime), true);
session_start();

// --- Servicios ---
$users = new UserRepository($db);
$polls = new PollRepository($db);
$answers = new AnswerRepository($db);
$passwords = new PasswordService();
$counterStore = new DbAttemptCounterStore($db);
$lockout = new AccountLockout($counterStore);
$rateLimiter = new RateLimiter($counterStore);
$auth = new AuthService($users, $passwords, $lockout, $logger);
$tokens = new TokenService(Config::appKey());
// Correo: SMTP si está configurado; si no, en development se escribe en el log.
// En cualquier otro entorno es un error de configuración.
if (Config::get('SMTP_HOST') !== '') {
    $email = new SmtpEmailSender(
        Config::get('SMTP_HOST'),
        (int) Config::get('SMTP_PORT', '587'),
        Config::get('SMTP_STARTTLS', 'true') === 'true',
        Config::get('SMTP_USER'),
        Config::get('SMTP_PASSWORD'),
        Config::get('SMTP_FROM', 'Encuestas <no-reply@encuestas.local>'),
    );
} elseif ($isDevelopment) {
    $email = new LoggingEmailSender($logger);
} else {
    throw new RuntimeException('Falta SMTP_HOST para enviar los correos.');
}
$view = new View($auth);

$errorController = new ErrorController($view, $auth, $request);

try {
    // Rechaza la sesión si el sello de seguridad ya no coincide con la BD
    // (p. ej. la contraseña cambió en otra sesión).
    $auth->validateSecurityStamp();

    // Expiración deslizante: cada petición autenticada renueva la cookie,
    // así la sesión caduca a las 8 horas de inactividad, no desde el login.
    if ($auth->isAuthenticated()) {
        setcookie((string) session_name(), (string) session_id(), [
            'expires' => time() + $sessionLifetime,
            'path' => $sessionCookieParams['path'],
            'httponly' => $sessionCookieParams['httponly'],
            'samesite' => $sessionCookieParams['samesite'],
            'secure' => $sessionCookieParams['secure'],
        ]);
    }

    // Protección CSRF en todos los POST.
    if ($method === 'POST' && !Csrf::validate()) {
        http_response_code(400);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Solicitud no válida (token CSRF ausente o incorrecto).';
        return;
    }

    $userController = new UserController(
        $view,
        $auth,
        $request,
        $users,
        $polls,
        $passwords,
        $tokens,
        $email,
        $rateLimiter,
        $logger
    );
    $accountController = new AccountController($view, $auth, $request, $users, $passwords, $tokens, $email, $logger);
    $pollController = new PollController($view, $auth, $request, $polls, $logger);
    $answerController = new AnswerController($view, $auth, $request, $polls, $answers);

    $router = new Router($auth);

    $router->get('user/index', fn () => $userController->index());
    $router->post('user/login', fn () => $userController->login());
    $router->post('user/register', fn () => $userController->register());
    $router->get('user/confirmemail', fn () => $userController->confirmEmail());
    $router->get('user/forgotpassword', fn () => $userController->forgotPassword());
    $router->post('user/forgotpassword', fn () => $userController->forgotPasswordPost());
    $router->get('user/resetpassword', fn () => $userController->resetPassword());
    $router->post('user/resetpassword', fn () => $userController->resetPasswordPost());
    $router->post('user/closesession', fn () => $userController->closeSession(), requiresAuth: true);

    $router->get('account/editprofile', fn () => $accountController->editProfile(), requiresAuth: true);
    $router->post('account/editprofile', fn () => $accountController->editProfilePost(), requiresAuth: true);
    $router->get('account/changeuser', fn () => $accountController->changeUser(), requiresAuth: true);
    $router->post('account/changeuser', fn () => $accountController->changeUserPost(), requiresAuth: true);
    $router->get('account/changeemail', fn () => $accountController->changeEmail(), requiresAuth: true);
    $router->post('account/changeemail', fn () => $accountController->changeEmailPost(), requiresAuth: true);
    $router->get('account/confirmemailchange', fn () => $accountController->confirmEmailChange());
    $router->get('account/changepassword', fn () => $accountController->changePassword(), requiresAuth: true);
    $router->post('account/changepassword', fn () => $accountController->changePasswordPost(), requiresAuth: true);
    $router->get('account/deleteaccount', fn () => $accountController->deleteAccount(), requiresAuth: true);
    $router->post('account/deleteaccount', fn () => $accountController->deleteAccountPost(), requiresAuth: true);

    $router->get('poll/index', fn () => $pollController->index(), requiresAuth: true);
    $router->get('poll/add', fn () => $pollController->add(), requiresAuth: true);
    $router->post('poll/add', fn () => $pollController->addPost(), requiresAuth: true);
    $router->get('poll/edit', fn (int $id) => $pollController->edit($id), requiresAuth: true);
    $router->post('poll/update', fn () => $pollController->update(), requiresAuth: true);
    $router->post('poll/delete', fn (int $id) => $pollController->delete($id), requiresAuth: true);

    $router->get('answer/add', fn (int $id) => $answerController->add($id), requiresAuth: true);
    $router->post('answer/add', fn (int $id) => $answerController->addPost($id), requiresAuth: true);
    $router->get('answer/answers', fn (int $id) => $answerController->answers($id), requiresAuth: true);

    $router->dispatch($method, $path);
} catch (NotFoundException) {
    $errorController->notFound();
} catch (Throwable $exception) {
    $logger->error('Excepción no controlada: ' . $exception->getMessage(), [
        'file' => $exception->getFile(),
        'line' => $exception->getLine(),
    ]);
    if ($isDevelopment) {
        throw $exception;
    }
    $errorController->serverError();
}
