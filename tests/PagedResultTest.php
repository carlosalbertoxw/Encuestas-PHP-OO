<?php

declare(strict_types=1);

namespace Encuestas\Tests;

use Encuestas\Data\PagedResult;
use PHPUnit\Framework\TestCase;

final class PagedResultTest extends TestCase
{
    public function test_calcula_el_total_de_paginas_redondeando_hacia_arriba(): void
    {
        $result = new PagedResult([], 1, 10, 21);

        $this->assertSame(3, $result->totalPages());
    }

    public function test_primera_pagina_sin_anterior_y_con_siguiente(): void
    {
        $result = new PagedResult([], 1, 10, 21);

        $this->assertFalse($result->hasPrevious());
        $this->assertTrue($result->hasNext());
    }

    public function test_ultima_pagina_con_anterior_y_sin_siguiente(): void
    {
        $result = new PagedResult([], 3, 10, 21);

        $this->assertTrue($result->hasPrevious());
        $this->assertFalse($result->hasNext());
    }

    public function test_sin_resultados_no_hay_paginas(): void
    {
        $result = new PagedResult([], 1, 10, 0);

        $this->assertSame(0, $result->totalPages());
        $this->assertFalse($result->hasNext());
    }
}
