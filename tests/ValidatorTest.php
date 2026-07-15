<?php

declare(strict_types=1);

namespace Encuestas\Tests;

use Encuestas\Web\Validator;
use PHPUnit\Framework\TestCase;

final class ValidatorTest extends TestCase
{
    public function test_required_falla_con_cadena_vacia_o_espacios(): void
    {
        $this->assertFalse((new Validator())->required('Campo', '')->isValid());
        $this->assertFalse((new Validator())->required('Campo', '   ')->isValid());
        $this->assertTrue((new Validator())->required('Campo', 'valor')->isValid());
    }

    public function test_email_valida_el_formato(): void
    {
        $this->assertTrue((new Validator())->email('Email', 'demo@encuestas.dev')->isValid());
        $this->assertFalse((new Validator())->email('Email', 'no-es-correo')->isValid());
    }

    public function test_length_respeta_minimo_y_maximo(): void
    {
        $this->assertFalse((new Validator())->length('Password', 'abc', 6, 50)->isValid());
        $this->assertFalse((new Validator())->length('Password', str_repeat('a', 51), 6, 50)->isValid());
        $this->assertTrue((new Validator())->length('Password', 'abcdef', 6, 50)->isValid());
    }

    public function test_match_compara_los_valores(): void
    {
        $this->assertTrue((new Validator())->match('RePassword', 'igual', 'igual')->isValid());
        $this->assertFalse((new Validator())->match('RePassword', 'uno', 'otro')->isValid());
    }

    public function test_pattern_valida_el_nombre_de_usuario(): void
    {
        $pattern = '/^[0-9a-zA-Z-]+$/';
        $this->assertTrue((new Validator())->pattern('UserName', 'usuario-1', $pattern, 'x')->isValid());
        $this->assertFalse((new Validator())->pattern('UserName', 'usuario con espacios', $pattern, 'x')->isValid());
    }

    public function test_range_valida_los_extremos(): void
    {
        $this->assertTrue((new Validator())->range('Stars', 1, 1, 5)->isValid());
        $this->assertTrue((new Validator())->range('Stars', 5, 1, 5)->isValid());
        $this->assertFalse((new Validator())->range('Stars', 0, 1, 5)->isValid());
        $this->assertFalse((new Validator())->range('Stars', 6, 1, 5)->isValid());
    }

    public function test_reporta_el_primer_error_por_campo(): void
    {
        $validator = (new Validator())
            ->required('Email', '')
            ->email('Email', '')
            ->required('Password', 'ok123456');

        $this->assertSame(['Email' => 'Este campo es obligatorio'], $validator->errors());
    }
}
