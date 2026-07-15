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
        (new MigrationRunner(self::$database, new Logger()))->run();
        self::$database->connection()->exec('DELETE FROM a_users');
        self::$database->connection()->exec('DELETE FROM a_attempt_counters');
        self::$database->connection()->exec('DELETE FROM a_sessions');
        (new DevDataSeeder(
            new UserRepository(self::$database),
            new PollRepository(self::$database),
            new AnswerRepository(self::$database),
            new PasswordService(),
            new Logger()
        ))->seed();

        self::startServer([
            'DB_HOST' => $host,
            'DB_PORT' => $port,
            'DB_DATABASE' => $name,
            'DB_USERNAME' => $user,
            'DB_PASSWORD' => $password,
            'APP_ENV' => 'development',
            'APP_KEY' => 'clave-de-pruebas-http',
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

    public function test_health_responde_saludable(): void
    {
        $response = $this->request('GET', '/health');

        $this->assertSame(200, $response['status']);
        $this->assertSame('Healthy', $response['body']);
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
            $probe = @file_get_contents(self::$baseUrl . '/health');
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
        $response = $this->request('GET', '/User/Index', $jar);
        if (preg_match('/name="_csrf" value="([^"]+)"/', $response['body'], $matches) !== 1) {
            $this->fail('La página de inicio no contiene el token CSRF.');
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
            curl_close($handle);
            $this->fail("La petición {$method} {$path} falló: {$error}");
        }
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        $headerSize = (int) curl_getinfo($handle, CURLINFO_HEADER_SIZE);
        curl_close($handle);

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
