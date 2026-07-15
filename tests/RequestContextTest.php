<?php

declare(strict_types=1);

namespace Encuestas\Tests;

use Encuestas\Web\RequestContext;
use PHPUnit\Framework\TestCase;

final class RequestContextTest extends TestCase
{
    public function test_sin_proxies_confiables_usa_remote_addr_e_ignora_forwarded(): void
    {
        $context = new RequestContext([], [
            'REMOTE_ADDR' => '203.0.113.7',
            'HTTP_X_FORWARDED_FOR' => '10.0.0.9',
            'HTTP_X_FORWARDED_PROTO' => 'https',
        ]);

        $this->assertSame('203.0.113.7', $context->clientIp());
        $this->assertFalse($context->isHttps(), 'X-Forwarded-Proto no debe aceptarse de un cliente cualquiera');
    }

    public function test_desde_un_proxy_confiable_toma_la_ip_del_cliente_en_forwarded_for(): void
    {
        $context = new RequestContext(['10.0.0.1'], [
            'REMOTE_ADDR' => '10.0.0.1',
            'HTTP_X_FORWARDED_FOR' => '203.0.113.7',
        ]);

        $this->assertSame('203.0.113.7', $context->clientIp());
    }

    public function test_recorre_la_cadena_saltando_proxies_confiables(): void
    {
        // El cliente malicioso antepone una IP falsa; la última no confiable gana.
        $context = new RequestContext(['10.0.0.0/8'], [
            'REMOTE_ADDR' => '10.0.0.1',
            'HTTP_X_FORWARDED_FOR' => '1.2.3.4, 203.0.113.7, 10.0.0.2',
        ]);

        $this->assertSame('203.0.113.7', $context->clientIp());
    }

    public function test_acepta_rangos_cidr(): void
    {
        $context = new RequestContext(['172.16.0.0/12'], [
            'REMOTE_ADDR' => '172.22.0.5',
            'HTTP_X_FORWARDED_FOR' => '203.0.113.7',
            'HTTP_X_FORWARDED_PROTO' => 'https',
        ]);

        $this->assertSame('203.0.113.7', $context->clientIp());
        $this->assertTrue($context->isHttps());
    }

    public function test_una_ip_fuera_del_rango_no_es_confiable(): void
    {
        $context = new RequestContext(['172.16.0.0/12'], [
            'REMOTE_ADDR' => '172.32.0.5',
            'HTTP_X_FORWARDED_FOR' => '203.0.113.7',
        ]);

        $this->assertSame('172.32.0.5', $context->clientIp());
    }

    public function test_https_directo_se_detecta_sin_proxies(): void
    {
        $context = new RequestContext([], [
            'REMOTE_ADDR' => '203.0.113.7',
            'HTTPS' => 'on',
        ]);

        $this->assertTrue($context->isHttps());
    }

    public function test_url_absoluta_usa_esquema_y_host(): void
    {
        $context = new RequestContext(['10.0.0.1'], [
            'REMOTE_ADDR' => '10.0.0.1',
            'HTTP_X_FORWARDED_PROTO' => 'https',
            'HTTP_HOST' => 'encuestas.example',
        ]);

        $this->assertSame('https://encuestas.example/User/Index', $context->absoluteUrl('/User/Index'));
    }
}
