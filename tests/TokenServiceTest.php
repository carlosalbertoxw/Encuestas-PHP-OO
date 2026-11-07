<?php

declare(strict_types=1);

namespace Encuestas\Tests;

use Encuestas\Web\Services\TokenService;
use PHPUnit\Framework\TestCase;

final class TokenServiceTest extends TestCase
{
    private TokenService $service;

    protected function setUp(): void
    {
        $this->service = new TokenService('clave-de-pruebas');
    }

    public function test_el_token_de_confirmacion_hace_el_viaje_completo(): void
    {
        $token = $this->service->createEmailConfirmationToken(42);

        $this->assertSame(42, $this->service->validateEmailConfirmationToken($token));
    }

    public function test_el_token_de_restablecimiento_conserva_el_sello(): void
    {
        $token = $this->service->createPasswordResetToken(7, 'sello-abc');

        $this->assertSame(
            ['userId' => 7, 'securityStamp' => 'sello-abc'],
            $this->service->validatePasswordResetToken($token)
        );
    }

    public function test_un_token_manipulado_es_rechazado(): void
    {
        $token = $this->service->createEmailConfirmationToken(42);

        $this->assertNull($this->service->validateEmailConfirmationToken($token . 'x'));
        $this->assertNull($this->service->validateEmailConfirmationToken('x' . $token));
        $this->assertNull($this->service->validateEmailConfirmationToken('basura'));
        $this->assertNull($this->service->validateEmailConfirmationToken(''));
    }

    public function test_un_token_firmado_con_otra_clave_es_rechazado(): void
    {
        $otherService = new TokenService('otra-clave');
        $token = $otherService->createEmailConfirmationToken(42);

        $this->assertNull($this->service->validateEmailConfirmationToken($token));
    }

    public function test_cada_proposito_solo_acepta_sus_propios_tokens(): void
    {
        $confirm = $this->service->createEmailConfirmationToken(42);
        $reset = $this->service->createPasswordResetToken(42, 'sello');

        $this->assertNull($this->service->validatePasswordResetToken($confirm));
        $this->assertNull($this->service->validateEmailConfirmationToken($reset));
        $this->assertNull($this->service->validateEmailChangeToken($confirm));
        $this->assertNull($this->service->validateEmailConfirmationToken(
            $this->service->createEmailChangeToken(42, 'a@b.dev')
        ));
    }

    public function test_el_token_de_cambio_de_correo_conserva_direcciones_raras(): void
    {
        $token = $this->service->createEmailChangeToken(7, 'nombre:raro@pruebas.dev');

        $this->assertSame(
            ['userId' => 7, 'email' => 'nombre:raro@pruebas.dev'],
            $this->service->validateEmailChangeToken($token)
        );
    }
}
