<?php

declare(strict_types=1);

namespace Encuestas\Tests;

use Encuestas\Web\Services\AccountLockout;
use Encuestas\Web\Services\InMemoryAttemptCounterStore;
use Encuestas\Web\Services\RateLimiter;
use PHPUnit\Framework\TestCase;

final class AccountLockoutTest extends TestCase
{
    public function test_bloquea_tras_el_maximo_de_intentos_fallidos(): void
    {
        $lockout = new AccountLockout(new InMemoryAttemptCounterStore());

        for ($i = 0; $i < AccountLockout::MAX_ATTEMPTS - 1; $i++) {
            $lockout->recordFailure('demo@encuestas.dev');
            $this->assertFalse($lockout->isLocked('demo@encuestas.dev'));
        }

        $lockout->recordFailure('demo@encuestas.dev');
        $this->assertTrue($lockout->isLocked('demo@encuestas.dev'));
    }

    public function test_el_bloqueo_ignora_mayusculas_del_correo(): void
    {
        $lockout = new AccountLockout(new InMemoryAttemptCounterStore());

        for ($i = 0; $i < AccountLockout::MAX_ATTEMPTS; $i++) {
            $lockout->recordFailure('Demo@Encuestas.dev');
        }

        $this->assertTrue($lockout->isLocked('demo@encuestas.dev'));
    }

    public function test_reset_limpia_el_contador(): void
    {
        $lockout = new AccountLockout(new InMemoryAttemptCounterStore());

        for ($i = 0; $i < AccountLockout::MAX_ATTEMPTS; $i++) {
            $lockout->recordFailure('demo@encuestas.dev');
        }
        $lockout->reset('demo@encuestas.dev');

        $this->assertFalse($lockout->isLocked('demo@encuestas.dev'));
    }

    public function test_las_cuentas_no_comparten_contador(): void
    {
        $lockout = new AccountLockout(new InMemoryAttemptCounterStore());

        for ($i = 0; $i < AccountLockout::MAX_ATTEMPTS; $i++) {
            $lockout->recordFailure('demo@encuestas.dev');
        }

        $this->assertFalse($lockout->isLocked('ana@encuestas.dev'));
    }

    public function test_el_rate_limiter_rechaza_al_superar_el_limite(): void
    {
        $limiter = new RateLimiter(new InMemoryAttemptCounterStore());

        for ($i = 0; $i < RateLimiter::PERMIT_LIMIT; $i++) {
            $this->assertFalse($limiter->isRejected('10.0.0.1'));
        }

        $this->assertTrue($limiter->isRejected('10.0.0.1'));
        $this->assertFalse($limiter->isRejected('10.0.0.2'));
    }
}
