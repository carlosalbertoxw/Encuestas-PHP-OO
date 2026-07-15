<?php

declare(strict_types=1);

namespace Encuestas\Tests;

use Encuestas\Web\Services\PasswordService;
use Encuestas\Web\Services\PasswordVerificationOutcome;
use PHPUnit\Framework\TestCase;

final class PasswordServiceTest extends TestCase
{
    private PasswordService $service;

    protected function setUp(): void
    {
        $this->service = new PasswordService();
    }

    public function test_verifica_la_contrasena_correcta(): void
    {
        $hash = $this->service->hash('secreta123');

        $this->assertSame(PasswordVerificationOutcome::Success, $this->service->verify($hash, 'secreta123'));
    }

    public function test_rechaza_la_contrasena_incorrecta(): void
    {
        $hash = $this->service->hash('secreta123');

        $this->assertSame(PasswordVerificationOutcome::Failed, $this->service->verify($hash, 'otra'));
    }

    public function test_un_hash_corrupto_se_trata_como_credencial_invalida(): void
    {
        $this->assertSame(PasswordVerificationOutcome::Failed, $this->service->verify('no-es-un-hash', 'secreta123'));
        $this->assertSame(PasswordVerificationOutcome::Failed, $this->service->verify('', 'secreta123'));
    }

    public function test_un_hash_con_parametros_antiguos_pide_regenerarse(): void
    {
        // Un costo menor al de PASSWORD_DEFAULT simula un hash legado.
        $legacyHash = password_hash('secreta123', PASSWORD_BCRYPT, ['cost' => 4]);

        $this->assertSame(PasswordVerificationOutcome::SuccessRehashNeeded, $this->service->verify($legacyHash, 'secreta123'));
    }

    public function test_cada_hash_lleva_sal_distinta(): void
    {
        $this->assertNotSame($this->service->hash('secreta123'), $this->service->hash('secreta123'));
    }
}
