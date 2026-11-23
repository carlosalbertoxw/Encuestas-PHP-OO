<?php

declare(strict_types=1);

namespace Encuestas\Tests;

use Encuestas\Data\AnswerRepository;
use Encuestas\Data\Database;
use Encuestas\Data\PollRepository;
use Encuestas\Data\UserRepository;
use Encuestas\Infrastructure\DevDataSeeder;
use Encuestas\Infrastructure\Logger;
use Encuestas\Infrastructure\MigrationRunner;
use Encuestas\Web\Services\PasswordService;
use PHPUnit\Framework\TestCase;

/**
 * Pruebas de humo del pipeline HTTP completo (front controller, router,
 * sesión, CSRF y autenticación): arrancan la aplicación real con el servidor
 * embebido de PHP contra la base de datos de pruebas. Se omiten si no se
 * define TEST_DB_HOST, igual que las de integración.
 */
final class HttpEndpointTest extends TestCase
{
    private const PASSWORD = 'Secreta123';

    private static ?Database $database = null;

    /** @var resource|null */
    private static $server = null;

    private static string $baseUrl = '';

    public static function setUpBeforeClass(): void
    {
        $host = getenv('TEST_DB_HOST');
        if ($host === false || $host === '') {
            return;
        }

        $port = getenv('TEST_DB_PORT') ?: '3306';
        $name = getenv('TEST_DB_DATABASE') ?: 'encuestas_test';
        $user = getenv('TEST_DB_USERNAME') ?: 'root';
        $password = getenv('TEST_DB_PASSWORD') ?: 'root';

        self::$database = new Database($host, (int) $port, $name, $user, $password);
        (new MigrationRunner(self::$database, new Logger('', 'php://memory')))->run();
        self::$database->connection()->exec('DELETE FROM a_users');
        self::$database->connection()->exec('DELETE FROM a_attempt_counters');
        self::$database->connection()->exec('DELETE FROM a_sessions');
        self::$database->connection()->exec('DELETE FROM a_outbox');
        (new DevDataSeeder(
            new UserRepository(self::$database),
            new PollRepository(self::$database),
            new AnswerRepository(self::$database),
            new PasswordService(),
            new Logger('', 'php://memory')
        ))->seed();

        self::startServer([
            'DB_HOST' => $host,
            'DB_PORT' => $port,
            'DB_DATABASE' => $name,
            'DB_USERNAME' => $user,
            'DB_PASSWORD' => $password,
            'APP_ENV' => 'development',
            'APP_KEY' => 'clave-de-pruebas-http',
            // Todas las pruebas salen de la misma IP: se amplían los límites.
            'RATE_LIMIT_AUTH' => '10000',
            'RATE_LIMIT_EMAIL_CHANGE' => '10000',
        ]);
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$server !== null) {
            proc_terminate(self::$server);
            proc_close(self::$server);
            self::$server = null;
        }
    }

    protected function setUp(): void
    {
        if (self::$server === null) {
            $this->markTestSkipped('Define TEST_DB_HOST para ejecutar las pruebas HTTP.');
        }
    }

    public function test_las_sondas_de_salud_responden_saludable(): void
    {
        foreach (['/health/live', '/health/ready'] as $path) {
            $response = $this->request('GET', $path);

            $this->assertSame(200, $response['status'], $path);
            $this->assertSame('Healthy', $response['body'], $path);
            $this->assertMatchesRegularExpression('/^[0-9a-f]{16}$/', $response['headers']['x-request-id'] ?? '');
        }
    }

    public function test_ruta_protegida_sin_sesion_redirige_al_login(): void
    {
        $response = $this->request('GET', '/Poll/Index');

        $this->assertSame(302, $response['status']);
        $this->assertStringContainsString('/User/Index', $response['headers']['location'] ?? '');
    }

    public function test_las_respuestas_llevan_cabeceras_de_seguridad(): void
    {
        $response = $this->request('GET', '/User/Index');

        $this->assertSame('nosniff', $response['headers']['x-content-type-options'] ?? '');
        $this->assertSame('DENY', $response['headers']['x-frame-options'] ?? '');
        $this->assertStringContainsString("default-src 'self'", $response['headers']['content-security-policy'] ?? '');
    }

    public function test_login_con_credenciales_demo_autentica(): void
    {
        $jar = $this->newCookieJar();
        $token = $this->getCsrfToken($jar);

        $response = $this->request('POST', '/User/Login', $jar, [
            '_csrf' => $token,
            'Email' => 'demo@encuestas.dev',
            'Password' => 'demo1234',
        ]);

        $this->assertSame(302, $response['status']);
        $this->assertStringContainsString('/Poll/Index', $response['headers']['location'] ?? '');

        $dashboard = $this->request('GET', '/Poll/Index', $jar);
        $this->assertSame(200, $dashboard['status']);
        $this->assertStringContainsString('Tablero', $dashboard['body']);
    }

    public function test_login_con_password_incorrecta_no_autentica(): void
    {
        $jar = $this->newCookieJar();
        $token = $this->getCsrfToken($jar);

        $response = $this->request('POST', '/User/Login', $jar, [
            '_csrf' => $token,
            'Email' => 'ana@encuestas.dev',
            'Password' => 'incorrecta',
        ]);

        $this->assertSame(200, $response['status']);
        $this->assertStringContainsString('Correo o contraseña incorrectos', $response['body']);

        $dashboard = $this->request('GET', '/Poll/Index', $jar);
        $this->assertSame(302, $dashboard['status'], 'Sin autenticar, el tablero debe redirigir');
    }

    public function test_un_post_sin_token_csrf_se_rechaza(): void
    {
        $response = $this->request('POST', '/User/Login', null, [
            'Email' => 'demo@encuestas.dev',
            'Password' => 'demo1234',
        ]);

        $this->assertSame(400, $response['status']);
    }

    public function test_registro_confirmacion_e_inicio_de_sesion(): void
    {
        $email = $this->newEmail();
        $jar = $this->newCookieJar();
        $this->register($jar, $email);
        $link = $this->latestLink($email, 'Confirma tu cuenta');

        // Antes de confirmar, el login se rechaza con un aviso.
        $beforeConfirm = $this->login($jar, $email, self::PASSWORD);
        $this->assertSame(200, $beforeConfirm['status']);
        $this->assertStringContainsString('Debes confirmar tu correo', $beforeConfirm['body']);

        $this->assertSame(302, $this->request('GET', $link, $jar)['status']);
        $this->assertRedirectsTo('/Poll/Index', $this->login($jar, $email, self::PASSWORD));
    }

    public function test_el_reenvio_de_confirmacion_manda_un_enlace_nuevo_valido(): void
    {
        $email = $this->newEmail();
        $jar = $this->newCookieJar();
        $this->register($jar, $email);

        $response = $this->post($jar, '/User/ResendConfirmation', '/User/ResendConfirmation', ['Email' => $email]);
        $this->assertStringContainsString('te enviamos un enlace nuevo', $response['body']);

        $this->request('GET', $this->latestLink($email, 'Confirma tu cuenta', 2), $jar);
        $this->assertRedirectsTo('/Poll/Index', $this->login($jar, $email, self::PASSWORD));
    }

    public function test_restablecer_la_contrasena_funciona_una_sola_vez(): void
    {
        $email = $this->createConfirmedAccount();
        $jar = $this->newCookieJar();
        $this->post($jar, '/User/ForgotPassword', '/User/ForgotPassword', ['Email' => $email]);
        $link = $this->latestLink($email, 'Restablece tu contraseña');
        parse_str((string) parse_url($link, PHP_URL_QUERY), $query);
        $token = $query['token'] ?? '';
        $fields = ['Token' => is_string($token) ? $token : '', 'NewPassword' => 'Nueva12345', 'ReNewPassword' => 'Nueva12345'];

        $this->assertRedirectsTo('/User/Index', $this->post($jar, $link, '/User/ResetPassword', $fields));
        $this->assertRedirectsTo('/Poll/Index', $this->login($this->newCookieJar(), $email, 'Nueva12345'));

        // El mismo enlace ya no sirve: el sello de seguridad cambió.
        $reused = $this->post($jar, '/User/ForgotPassword', '/User/ResetPassword', $fields);
        $this->assertStringContainsString('no es válido o expiró', $reused['body']);
    }

    public function test_el_cambio_de_correo_se_aplica_al_confirmar_desde_el_correo_nuevo(): void
    {
        $email = $this->createConfirmedAccount();
        $newEmail = $this->newEmail();
        $jar = $this->newCookieJar();
        $this->assertRedirectsTo('/Poll/Index', $this->login($jar, $email, self::PASSWORD));

        $this->post($jar, '/Account/ChangeEmail', '/Account/ChangeEmail', ['Email' => $newEmail, 'Password' => self::PASSWORD]);
        $this->latestLink($email, 'Solicitud de cambio de correo');
        $link = $this->latestLink($newEmail, 'Confirma tu nuevo correo');

        $this->assertSame(302, $this->request('GET', $link, $jar)['status']);
        $this->assertRedirectsTo('/Poll/Index', $this->login($this->newCookieJar(), $newEmail, self::PASSWORD));
        $this->assertSame(200, $this->login($this->newCookieJar(), $email, self::PASSWORD)['status']);
    }

    public function test_una_encuesta_invalida_vuelve_a_mostrar_el_formulario_con_lo_escrito(): void
    {
        $jar = $this->newCookieJar();
        $this->assertRedirectsTo('/Poll/Index', $this->login($jar, 'demo@encuestas.dev', 'demo1234'));

        $response = $this->post($jar, '/Poll/Add', '/Poll/Add', [
            'Title' => '',
            'Description' => 'Texto que no debe perderse',
            'Position' => '1',
        ]);

        $this->assertSame(200, $response['status']);
        $this->assertStringContainsString('Texto que no debe perderse', $response['body']);
    }

    private function newEmail(): string
    {
        return 'flujo-' . bin2hex(random_bytes(8)) . '@test.com';
    }

    private function createConfirmedAccount(): string
    {
        $email = $this->newEmail();
        $jar = $this->newCookieJar();
        $this->register($jar, $email);
        $this->request('GET', $this->latestLink($email, 'Confirma tu cuenta'), $jar);
        return $email;
    }

    private function register(string $jar, string $email): void
    {
        $response = $this->post($jar, '/User/Index', '/User/Register', [
            'Email' => $email,
            'Password' => self::PASSWORD,
            'RePassword' => self::PASSWORD,
        ]);
        $this->assertSame(200, $response['status']);
    }

    /** @return array{status: int, headers: array<string, string>, body: string} */
    private function login(string $jar, string $email, string $password): array
    {
        return $this->post($jar, '/User/Index', '/User/Login', ['Email' => $email, 'Password' => $password]);
    }

    /**
     * Abre $formPage para obtener el token CSRF y envía el formulario a $action.
     *
     * @param array<string, string> $fields
     * @return array{status: int, headers: array<string, string>, body: string}
     */
    private function post(string $jar, string $formPage, string $action, array $fields): array
    {
        return $this->request('POST', $action, $jar, ['_csrf' => $this->csrfFrom($jar, $formPage)] + $fields);
    }

    /**
     * @param non-empty-string $path
     * @param array{status: int, headers: array<string, string>, body: string} $response
     */
    private function assertRedirectsTo(string $path, array $response): void
    {
        $this->assertSame(302, $response['status']);
        $this->assertStringStartsWith($path, $response['headers']['location'] ?? '');
    }

    /**
     * Ruta (con su token) del enlace del correo más reciente con ese asunto que la
     * aplicación encoló para el destinatario. En estas pruebas no corre el
     * despachador, así que los correos se quedan en a_outbox.
     */
    private function latestLink(string $to, string $subject, int $count = 1): string
    {
        $statement = $this->database()->connection()->prepare(
            'SELECT o_body FROM a_outbox WHERE o_recipient=? AND o_subject=? ORDER BY o_key DESC'
        );
        $statement->execute([$to, $subject]);
        /** @var list<string> $bodies */
        $bodies = $statement->fetchAll(\PDO::FETCH_COLUMN);
        $this->assertGreaterThanOrEqual($count, count($bodies), "No se encoló el correo '{$subject}' para {$to}");
        if (preg_match('/href="([^"]+)"/', $bodies[0], $matches) !== 1) {
            return '';
        }
        $url = html_entity_decode($matches[1], ENT_QUOTES);
        return (string) parse_url($url, PHP_URL_PATH) . '?' . (string) parse_url($url, PHP_URL_QUERY);
    }

    private function database(): Database
    {
        if (self::$database === null) {
            $this->fail('Sin base de datos de pruebas.');
        }
        return self::$database;
    }

    /** @param array<string, string> $environment */
    private static function startServer(array $environment): void
    {
        $port = getenv('TEST_HTTP_PORT') ?: '8099';
        self::$baseUrl = 'http://127.0.0.1:' . $port;
        $root = dirname(__DIR__);

        $process = proc_open(
            [PHP_BINARY, '-S', '127.0.0.1:' . $port, '-t', $root . '/public', $root . '/public/index.php'],
            [1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']],
            $pipes,
            $root,
            $environment + ['PATH' => (string) getenv('PATH')]
        );
        if ($process === false) {
            throw new \RuntimeException('No se pudo arrancar el servidor embebido de PHP.');
        }
        self::$server = $process;

        // Espera a que el servidor acepte peticiones.
        for ($attempt = 0; $attempt < 50; $attempt++) {
            $probe = @file_get_contents(self::$baseUrl . '/health/live');
            if ($probe === 'Healthy') {
                return;
            }
            usleep(200_000);
        }
        throw new \RuntimeException('El servidor embebido no respondió a tiempo.');
    }

    private function newCookieJar(): string
    {
        $jar = tempnam(sys_get_temp_dir(), 'encuestas-cookies-');
        if ($jar === false) {
            $this->fail('No se pudo crear el archivo de cookies.');
        }
        return $jar;
    }

    /** Obtiene el token CSRF de la página de inicio, conservando la cookie de sesión en el jar. */
    private function getCsrfToken(string $jar): string
    {
        return $this->csrfFrom($jar, '/User/Index');
    }

    /** Obtiene el token CSRF de una página, conservando la cookie de sesión en el jar. */
    private function csrfFrom(string $jar, string $path): string
    {
        $response = $this->request('GET', $path, $jar);
        if (preg_match('/name="_csrf" value="([^"]+)"/', $response['body'], $matches) !== 1) {
            $this->fail("La página {$path} no contiene el token CSRF.");
        }
        return $matches[1];
    }

    /**
     * @param array<string, string>|null $postFields
     * @return array{status: int, headers: array<string, string>, body: string}
     */
    private function request(string $method, string $path, ?string $cookieJar = null, ?array $postFields = null): array
    {
        $handle = curl_init(self::$baseUrl . $path);
        if ($handle === false) {
            $this->fail('No se pudo inicializar curl.');
        }
        curl_setopt_array($handle, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 10,
        ]);
        if ($cookieJar !== null && $cookieJar !== '') {
            curl_setopt($handle, CURLOPT_COOKIEJAR, $cookieJar);
            curl_setopt($handle, CURLOPT_COOKIEFILE, $cookieJar);
        }
        if ($method === 'POST') {
            curl_setopt($handle, CURLOPT_POST, true);
            curl_setopt($handle, CURLOPT_POSTFIELDS, http_build_query($postFields ?? []));
        }

        $raw = curl_exec($handle);
        if (!is_string($raw)) {
            $error = curl_error($handle);
            $this->fail("La petición {$method} {$path} falló: {$error}");
        }
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        $headerSize = (int) curl_getinfo($handle, CURLINFO_HEADER_SIZE);

        $headers = [];
        foreach (explode("\r\n", substr($raw, 0, $headerSize)) as $line) {
            if (str_contains($line, ':')) {
                [$headerName, $value] = explode(':', $line, 2);
                $headers[strtolower(trim($headerName))] = trim($value);
            }
        }

        return ['status' => $status, 'headers' => $headers, 'body' => substr($raw, $headerSize)];
    }
}
