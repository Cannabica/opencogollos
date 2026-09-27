<?php

namespace App\Support;

use Illuminate\Validation\Rules\Password;

/**
 * Requisitos de contraseña, en un solo lugar.
 *
 * La regla la aplica Laravel (`Password::min(8)->letters()->mixedCase()->numbers()->symbols()`), pero
 * la UI necesita lo mismo **desglosado** para mostrarlo en vivo: "8 caracteres ✓ / una mayúscula ✗".
 * Si el desglose y la regla se escriben por separado, tarde o temprano mienten distinto: esto es una
 * única fuente para las dos.
 */
class PasswordRequirements
{
    public const MIN_LENGTH = 8;

    /**
     * La regla de validación, tal como la usan los formularios.
     *
     * @return array<int, Password>
     */
    public static function rule(): array
    {
        return [
            Password::min(self::MIN_LENGTH)
                ->letters()
                ->mixedCase()
                ->numbers()
                ->symbols(),
        ];
    }

    /**
     * Los requisitos con su estado para una contraseña dada (null = todavía no escribió nada).
     *
     * @return array<int, array{key: string, label: string, ok: bool}>
     */
    public static function checks(?string $password): array
    {
        $password = (string) $password;

        return [
            [
                'key' => 'length',
                'label' => 'Al menos ' . self::MIN_LENGTH . ' caracteres',
                'ok' => mb_strlen($password) >= self::MIN_LENGTH,
            ],
            [
                'key' => 'lowercase',
                'label' => 'Una letra minúscula',
                'ok' => preg_match('/[a-záéíóúüñ]/u', $password) === 1,
            ],
            [
                'key' => 'uppercase',
                'label' => 'Una letra mayúscula',
                'ok' => preg_match('/[A-ZÁÉÍÓÚÜÑ]/u', $password) === 1,
            ],
            [
                'key' => 'number',
                'label' => 'Un número',
                'ok' => preg_match('/[0-9]/', $password) === 1,
            ],
            [
                'key' => 'symbol',
                'label' => 'Un símbolo (!@#$%…)',
                'ok' => preg_match('/[^a-zA-Z0-9\sáéíóúüñÁÉÍÓÚÜÑ]/u', $password) === 1,
            ],
        ];
    }

    /**
     * ¿La contraseña cumple TODO? (para habilitar/avisar sin esperar al submit).
     */
    public static function passes(?string $password): bool
    {
        foreach (self::checks($password) as $check) {
            if (! $check['ok']) {
                return false;
            }
        }

        return true;
    }

    /**
     * Genera una contraseña que CUMPLE la política, para las claves que emite el sistema (altas de
     * usuarios del grupo).
     *
     * Antes se usaba `Str::random(12)`, que no garantiza mayúscula ni símbolo: se le mandaba por mail
     * al usuario una contraseña que el propio sistema le iba a rechazar al cambiarla. Acá se arma con
     * al menos uno de cada tipo y se mezcla. Se evitan caracteres ambiguos (l/I/1, O/0) porque esta
     * clave se copia a mano desde un mail.
     */
    public static function generate(int $length = 14): string
    {
        $lower = 'abcdefghijkmnopqrstuvwxyz';
        $upper = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $digits = '23456789';
        $symbols = '!@#$%&*?';

        $password = $lower[random_int(0, strlen($lower) - 1)]
            . $upper[random_int(0, strlen($upper) - 1)]
            . $digits[random_int(0, strlen($digits) - 1)]
            . $symbols[random_int(0, strlen($symbols) - 1)];

        $todos = $lower . $upper . $digits . $symbols;

        for ($i = strlen($password); $i < $length; $i++) {
            $password .= $todos[random_int(0, strlen($todos) - 1)];
        }

        return str_shuffle($password);
    }
}
