<?php

namespace Tests\Feature;

use App\Http\Middleware\RejectLineBreaksInEmails;
use Illuminate\Contracts\Http\Kernel as HttpKernelContract;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * Mitigacion del advisory HIGH de `laravel/framework` (CRLF injection en la regla `email`).
 *
 * La regla del framework aceptaba un valor como "yo@ejemplo.com\r\nBcc: otro@ejemplo.com" y la app manda
 * mails a direcciones que carga el usuario -> el salto de linea viajaba al encabezado del mail. El fix
 * solo existe en Laravel 12 (migracion diferida, tarjeta T5.5), asi que el middleware tapa la entrada.
 */
class RejectLineBreaksInEmailsTest extends TestCase
{
    private function pasar(array $input): void
    {
        $request = Request::create('/tenant/register', 'POST', $input);

        (new RejectLineBreaksInEmails)->handle($request, fn () => response('ok'));
    }

    public function test_rechaza_un_salto_de_linea_en_un_campo_de_email_singular(): void
    {
        try {
            $this->pasar(['email' => "yo@ejemplo.com\r\nBcc: otro@ejemplo.com"]);
            $this->fail('El middleware dejo pasar un email con salto de linea.');
        } catch (HttpException $e) {
            $this->assertSame(422, $e->getStatusCode());
        }
    }

    public function test_rechaza_inyeccion_en_la_lista_de_correos_del_equipo(): void
    {
        // team_emails es un textarea: los saltos de linea son legitimos, pero cada linea
        // tiene que ser una direccion valida.
        try {
            $this->pasar(['team_emails' => "juan@cultivo.com\nBcc: otro@ejemplo.com"]);
            $this->fail('El middleware dejo pasar una inyeccion dentro de la lista.');
        } catch (HttpException $e) {
            $this->assertSame(422, $e->getStatusCode());
        }
    }

    public function test_permite_una_lista_legitima_de_correos(): void
    {
        $this->pasar(['team_emails' => "juan@cultivo.com\nmaria@cultivo.com\r\n\nclub@semilladorada.org"]);
        $this->assertTrue(true);
    }

    public function test_permite_un_email_normal(): void
    {
        $this->pasar(['email' => 'juan@cultivo.com', 'name' => 'Juan', 'notes' => "linea 1\nlinea 2"]);
        $this->assertTrue(true);
    }

    public function test_la_mitigacion_sigue_registrada_en_el_grupo_web(): void
    {
        $grupos = app(HttpKernelContract::class)->getMiddlewareGroups();

        $this->assertContains(
            RejectLineBreaksInEmails::class,
            $grupos['web'],
            'El middleware se desregistro del grupo web: la entrada de emails quedo sin filtrar.'
        );
    }
}
