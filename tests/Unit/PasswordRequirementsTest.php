<?php

namespace Tests\Unit;

use App\Support\PasswordRequirements;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * El desglose visual y la regla de validación tienen que decir LO MISMO.
 *
 * La UI muestra "8 caracteres ✓ / una mayúscula ✗" usando `PasswordRequirements::checks()`, y el
 * formulario valida con `PasswordRequirements::rule()`. Si esas dos cosas se separan, la pantalla
 * miente (dice que está todo bien y el submit falla, o al revés). Los tests de coherencia de abajo
 * son los que impiden eso.
 */
class PasswordRequirementsTest extends TestCase
{
    public function test_desglosa_los_cinco_requisitos(): void
    {
        $checks = PasswordRequirements::checks(null);

        $this->assertCount(5, $checks);
        $this->assertSame(
            ['length', 'lowercase', 'uppercase', 'number', 'symbol'],
            array_column($checks, 'key')
        );
    }

    public function test_una_contrasena_vacia_no_cumple_nada(): void
    {
        foreach (PasswordRequirements::checks(null) as $check) {
            $this->assertFalse($check['ok'], $check['key'] . ' no puede estar ok con la contraseña vacía.');
        }
        $this->assertFalse(PasswordRequirements::passes(null));
        $this->assertFalse(PasswordRequirements::passes(''));
    }

    public function test_marca_solo_lo_que_corresponde(): void
    {
        // Sólo minúsculas y largo suficiente: cumple esos dos y nada más.
        $checks = collect(PasswordRequirements::checks('abcdefgh'))->keyBy('key');

        $this->assertTrue($checks['length']['ok']);
        $this->assertTrue($checks['lowercase']['ok']);
        $this->assertFalse($checks['uppercase']['ok']);
        $this->assertFalse($checks['number']['ok']);
        $this->assertFalse($checks['symbol']['ok']);
    }

    public function test_una_contrasena_fuerte_cumple_todo(): void
    {
        $this->assertTrue(PasswordRequirements::passes('NuevaClave2@'));
    }

    public function test_el_desglose_coincide_con_la_regla_de_validacion(): void
    {
        $casos = ['abc', 'abcdefgh', 'Abcdefgh', 'Abcdefg1', 'Abcdefg1@', 'NuevaClave2@', 'CORTA1@'];

        foreach ($casos as $password) {
            $pasaLaRegla = Validator::make(
                ['new_password' => $password],
                ['new_password' => PasswordRequirements::rule()]
            )->passes();

            $this->assertSame(
                $pasaLaRegla,
                PasswordRequirements::passes($password),
                "El desglose visual y la regla no coinciden para '{$password}'."
            );
        }
    }
}
