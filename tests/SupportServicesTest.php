<?php

declare(strict_types=1);

namespace Encuestas\Tests;

use Encuestas\Config;
use Encuestas\Infrastructure\Logger;
use Encuestas\Web\Services\LogRedaction;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class SupportServicesTest extends TestCase
{
    protected function tearDown(): void
    {
        putenv('APP_BASE_URL');
        putenv('APP_ENV');
    }

    public function test_enmascara_el_correo_en_los_logs(): void
    {
        $this->assertSame('d***@encuestas.dev', LogRedaction::email('demo@encuestas.dev'));
        $this->assertSame('***', LogRedaction::email('sin-arroba'));
        $this->assertSame('', LogRedaction::email(''));
    }

    public function test_los_enlaces_usan_la_url_configurada(): void
    {
        putenv('APP_BASE_URL=https://encuestas.example.com/');

        $this->assertSame(
            'https://encuestas.example.com/User/ResetPassword?token=abc',
            Config::absoluteUrl('/User/ResetPassword?token=abc')
        );
    }

    public function test_sin_url_valida_en_produccion_falla(): void
    {
        putenv('APP_ENV=production');
        putenv('APP_BASE_URL=ftp://encuestas.example.com');

        $this->expectException(RuntimeException::class);
        Config::baseUrl();
    }

    public function test_el_logger_escribe_json_con_el_id_de_peticion(): void
    {
        $file = (string) tempnam(sys_get_temp_dir(), 'log');
        try {
            $logger = new Logger('abc123', $file);
            $logger->warning("Hola \"mundo\"\nadiós", ['userId' => 7]);

            $entry = json_decode(trim((string) file_get_contents($file)), true);
            $this->assertIsArray($entry);
            $this->assertSame('WARNING', $entry['level']);
            $this->assertSame("Hola \"mundo\"\nadiós", $entry['message']);
            $this->assertSame('abc123', $entry['requestId']);
            $this->assertSame(['userId' => 7], $entry['context']);
        } finally {
            @unlink($file);
        }
    }
}
