<?php

declare(strict_types=1);

namespace Encuestas\Web;

use Encuestas\Config;
use Encuestas\Data\AnswerRepository;
use Encuestas\Data\Database;
use Encuestas\Data\EmailOutbox;
use Encuestas\Data\PollRepository;
use Encuestas\Data\UserRepository;
use Encuestas\Infrastructure\Logger;
use Encuestas\Web\Controllers\AccountController;
use Encuestas\Web\Controllers\AnswerController;
use Encuestas\Web\Controllers\ErrorController;
use Encuestas\Web\Controllers\PollController;
use Encuestas\Web\Controllers\UserController;
use Encuestas\Web\Services\AccountLockout;
use Encuestas\Web\Services\AccountService;
use Encuestas\Web\Services\AuthService;
use Encuestas\Web\Services\DbAttemptCounterStore;
use Encuestas\Web\Services\PasswordService;
use Encuestas\Web\Services\RateLimiter;
use Encuestas\Web\Services\TokenService;
use Throwable;

/**
 * Atiende una petición: sondas de salud, redirección a HTTPS, cabeceras de
 * seguridad, sesión en MySQL, CSRF y despacho a los controladores. Construye
 * los servicios (composición manual, sin contenedor) y la tabla de rutas.
 */
final class Application
{
    /** Expiración deslizante de la sesión: 8 horas de inactividad. */
    private const SESSION_LIFETIME = 8 * 3600;

    public function __construct(private readonly Logger $logger)
    {
    }

    public function handle(string $method, string $path): void
    {
        // Liveness: solo confirma que PHP responde (sin BD, para que una caída de
        // MySQL no provoque reinicios en bucle).
        if ($path === '/health/live') {
            $this->plainText('Healthy');
            return;
        }

        $db = Database::fromEnvironment();
        if ($path === '/health/ready') {
            $this->readiness($db);
            return;
        }

        $isDevelopment = Config::isDevelopment();
        // Las cabeceras X-Forwarded-* solo se aceptan desde proxies declarados en TRUSTED_PROXIES.
        $request = RequestContext::fromEnvironment();
        if ($this->redirectToHttps($request, $isDevelopment)) {
            return;
        }
        $this->sendSecurityHeaders(!$isDevelopment && $request->isHttps());
        $cookie = $this->startSession($db, $isDevelopment ? $request->isHttps() : true);

        $users = new UserRepository($db);
        $polls = new PollRepository($db);
        $passwords = new PasswordService();
        $counters = new DbAttemptCounterStore($db);
        $auth = new AuthService($users, $passwords, new AccountLockout($counters), $this->logger);
        $view = new View($auth);
        $errorController = new ErrorController($view, $auth, $request);

        try {
            // Rechaza la sesión si el sello de seguridad ya no coincide con la BD
            // (p. ej. la contraseña cambió en otra sesión).
            $auth->validateSecurityStamp();

            // Expiración deslizante: cada petición autenticada renueva la cookie.
            if ($auth->isAuthenticated()) {
                $options = ['expires' => time() + self::SESSION_LIFETIME] + $cookie;
                setcookie((string) session_name(), (string) session_id(), $options);
            }

            // Protección CSRF en todos los POST.
            if ($method === 'POST' && !Csrf::validate()) {
                http_response_code(400);
                $this->plainText('Solicitud no válida (token CSRF ausente o incorrecto).');
                return;
            }

            $tokens = new TokenService(Config::appKey());
            $accounts = new AccountService($users, $passwords, $tokens, new EmailOutbox($db), $this->logger);
            $router = new Router($auth);
            $this->registerRoutes(
                $router,
                new UserController(
                    $view,
                    $auth,
                    $request,
                    $users,
                    $polls,
                    $accounts,
                    new RateLimiter($counters, 'auth', Config::positiveInt('RATE_LIMIT_AUTH', 10), 60),
                ),
                new AccountController(
                    $view,
                    $auth,
                    $request,
                    $users,
                    $accounts,
                    new RateLimiter($counters, 'email-change', Config::positiveInt('RATE_LIMIT_EMAIL_CHANGE', 3), 3600),
                ),
                new PollController($view, $auth, $request, $polls, $this->logger),
                new AnswerController($view, $auth, $request, $polls, new AnswerRepository($db)),
            );
            $router->dispatch($method, $path);
        } catch (NotFoundException) {
            $errorController->notFound();
        } catch (Throwable $exception) {
            $this->logger->error('Excepción no controlada: ' . $exception->getMessage(), [
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);
            if ($isDevelopment) {
                throw $exception;
            }
            $errorController->serverError();
        }
    }

    private function registerRoutes(
        Router $router,
        UserController $user,
        AccountController $account,
        PollController $poll,
        AnswerController $answer,
    ): void {
        $router->get('user/index', fn () => $user->index());
        $router->post('user/login', fn () => $user->login());
        $router->post('user/register', fn () => $user->register());
        $router->get('user/confirmemail', fn () => $user->confirmEmail());
        $router->get('user/resendconfirmation', fn () => $user->resendConfirmation());
        $router->post('user/resendconfirmation', fn () => $user->resendConfirmationPost());
        $router->get('user/forgotpassword', fn () => $user->forgotPassword());
        $router->post('user/forgotpassword', fn () => $user->forgotPasswordPost());
        $router->get('user/resetpassword', fn () => $user->resetPassword());
        $router->post('user/resetpassword', fn () => $user->resetPasswordPost());
        $router->post('user/closesession', fn () => $user->closeSession(), requiresAuth: true);

        $router->get('account/editprofile', fn () => $account->editProfile(), requiresAuth: true);
        $router->post('account/editprofile', fn () => $account->editProfilePost(), requiresAuth: true);
        $router->get('account/changeuser', fn () => $account->changeUser(), requiresAuth: true);
        $router->post('account/changeuser', fn () => $account->changeUserPost(), requiresAuth: true);
        $router->get('account/changeemail', fn () => $account->changeEmail(), requiresAuth: true);
        $router->post('account/changeemail', fn () => $account->changeEmailPost(), requiresAuth: true);
        $router->get('account/confirmemailchange', fn () => $account->confirmEmailChange());
        $router->get('account/changepassword', fn () => $account->changePassword(), requiresAuth: true);
        $router->post('account/changepassword', fn () => $account->changePasswordPost(), requiresAuth: true);
        $router->get('account/deleteaccount', fn () => $account->deleteAccount(), requiresAuth: true);
        $router->post('account/deleteaccount', fn () => $account->deleteAccountPost(), requiresAuth: true);

        $router->get('poll/index', fn () => $poll->index(), requiresAuth: true);
        $router->get('poll/add', fn () => $poll->add(), requiresAuth: true);
        $router->post('poll/add', fn () => $poll->addPost(), requiresAuth: true);
        $router->get('poll/edit', fn (int $id) => $poll->edit($id), requiresAuth: true);
        $router->post('poll/update', fn () => $poll->update(), requiresAuth: true);
        $router->post('poll/delete', fn (int $id) => $poll->delete($id), requiresAuth: true);

        $router->get('answer/add', fn (int $id) => $answer->add($id), requiresAuth: true);
        $router->post('answer/add', fn (int $id) => $answer->addPost($id), requiresAuth: true);
        $router->get('answer/answers', fn (int $id) => $answer->answers($id), requiresAuth: true);
    }

    /** Readiness: además de responder, comprueba la conectividad con la base de datos. */
    private function readiness(Database $db): void
    {
        try {
            $db->connection()->query('SELECT 1');
            $this->plainText('Healthy');
        } catch (Throwable) {
            http_response_code(503);
            $this->plainText('Unhealthy');
        }
    }

    /**
     * Redirección http -> https, activable con FORCE_HTTPS=true cuando la app
     * termina TLS (directamente o tras un proxy de confianza). Apagada en
     * desarrollo y por defecto. El destino sale de APP_BASE_URL, no de la cabecera Host.
     */
    private function redirectToHttps(RequestContext $request, bool $isDevelopment): bool
    {
        if ($isDevelopment || $request->isHttps() || Config::get('FORCE_HTTPS') !== 'true') {
            return false;
        }
        http_response_code(308);
        $host = (string) parse_url(Config::baseUrl(), PHP_URL_HOST);
        header('Location: https://' . $host . ($_SERVER['REQUEST_URI'] ?? '/'));
        return true;
    }

    private function sendSecurityHeaders(bool $hsts): void
    {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        // Sin scripts en línea; 'unsafe-inline' en estilos sigue por los estilos que
        // Bootstrap aplica dinámicamente a los menús desplegables.
        header(
            "Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; "
            . "img-src 'self' data:; frame-ancestors 'none'"
        );
        if ($hsts) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
    }

    /**
     * Sesión almacenada en MySQL (ver DbSessionHandler).
     *
     * @return array{path: string, httponly: bool, samesite: 'Lax', secure: bool} parámetros de la cookie
     */
    private function startSession(Database $db, bool $secure): array
    {
        // Solo caracteres alfanuméricos: PHP no admite puntos en el nombre de la sesión.
        session_name('EncuestasAuth');
        // En desarrollo la app corre sobre http; fuera de él la cookie solo viaja por HTTPS.
        $cookie = ['path' => '/', 'httponly' => true, 'samesite' => 'Lax', 'secure' => $secure];
        session_set_cookie_params(['lifetime' => self::SESSION_LIFETIME] + $cookie);
        // Garantiza que el recolector de sesiones caducadas corra de vez en cuando.
        ini_set('session.gc_probability', '1');
        ini_set('session.gc_divisor', '100');
        session_set_save_handler(new DbSessionHandler($db, self::SESSION_LIFETIME), true);
        session_start();
        return $cookie;
    }

    private function plainText(string $body): void
    {
        header('Content-Type: text/plain; charset=utf-8');
        echo $body;
    }
}
