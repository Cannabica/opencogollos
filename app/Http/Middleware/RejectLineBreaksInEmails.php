<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rechaza saltos de línea (\r, \n) en los campos de email de entrada.
 *
 * POR QUÉ: `laravel/framework` arrastra 3 advisories abiertos (uno **HIGH**, CVE-2026-48019) porque su
 * regla de validación `email` NO rechaza CR/LF. Un valor como
 *
 *     "yo@ejemplo.com\r\nBcc: otro@ejemplo.com"
 *
 * pasaba la validación como email válido, y como la app **manda mails a direcciones que carga el
 * usuario** (confirmación de registro, activación de tenant), ese salto de línea podía inyectar un
 * encabezado en el mail que sale de la plataforma: un relay de spam desde nuestra IP y nuestra cuenta
 * de Brevo. La superficie más expuesta es el **registro público del tenant** (anónimo).
 *
 * El fix de Laravel sólo existe en la 12 (dos majors arriba) y la migración se difirió a un sprint
 * propio (tarjeta **T5.5**). Esto **tapa la entrada** mientras tanto; cuando se migre, se retira sin
 * conflicto. NO se puede sobrescribir la regla `email` del framework (una extensión no gana sobre el
 * validador nativo), así que el filtro va acá.
 *
 * CÓMO: mira los campos cuyo nombre contiene "email" y distingue dos formas legítimas:
 *   · campo singular  → `a@x.com` pasa; **cualquier** salto de línea adentro es inválido
 *   · campo plural (termina en "emails", p. ej. el textarea `team_emails` = lista de correos del
 *     equipo, una por línea) → se permite el salto, pero **cada línea** tiene que ser una dirección
 *     válida (así `a@x.com\nBcc: evil@z` se rechaza, porque la segunda línea no es una dirección)
 */
class RejectLineBreaksInEmails
{
    public function handle(Request $request, Closure $next): Response
    {
        foreach ($request->all() as $campo => $valor) {
            if (! is_string($campo) || ! str_contains(mb_strtolower($campo), 'email')) {
                continue;
            }

            $esLista = str_ends_with(mb_strtolower($campo), 'emails');

            if ($this->esSospechoso($valor, $esLista)) {
                abort(422, 'El campo de correo contiene caracteres inválidos.');
            }
        }

        return $next($request);
    }

    private function esSospechoso(mixed $valor, bool $esLista): bool
    {
        if (is_array($valor)) {
            foreach ($valor as $item) {
                if ($this->esSospechoso($item, $esLista)) {
                    return true;
                }
            }

            return false;
        }

        if (! is_string($valor) || ! preg_match('/[\r\n]/', $valor)) {
            return false;
        }

        // Campo singular: un email no tiene saltos de línea, nunca.
        if (! $esLista) {
            return true;
        }

        // Campo lista: se permite el salto, pero cada línea tiene que ser una dirección válida.
        foreach (preg_split('/[\r\n]+/', $valor) as $linea) {
            $linea = trim($linea);

            if ($linea !== '' && ! filter_var($linea, FILTER_VALIDATE_EMAIL)) {
                return true;
            }
        }

        return false;
    }
}
