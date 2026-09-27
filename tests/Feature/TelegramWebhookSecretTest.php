<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyTelegramTenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * El webhook del bot tenant no puede aceptar updates que no vengan de Telegram (tarjeta T10.7).
 *
 * Medido el 2026-09-14: `POST /api/telegram/webhook` estaba registrado sólo con `api` y sin ninguna
 * verificación, y el middleware que debía protegerlo NUNCA estaba aplicado a una ruta. Cualquiera podía
 * mandar un update falso con el `from.id` que quisiera y el bot actuaba como ese usuario.
 *
 * Se cubren los tres rechazos (fail closed, sin header, secret incorrecto), el caso que debe pasar y
 * —clave— el WIRING: que el middleware siga colgado de la ruta (si alguien lo desaplica, este test falla).
 */
class TelegramWebhookSecretTest extends TestCase
{
    private const SECRET = 'secreto-de-prueba-1234567890';

    public function test_sin_secret_configurado_el_webhook_rechaza_todo_fail_closed(): void
    {
        config(['telegram.tenant_secret' => null]);

        $this->postJson('/api/telegram/webhook', ['update_id' => 1])->assertStatus(503);
    }

    public function test_sin_el_header_de_telegram_el_update_se_rechaza(): void
    {
        config(['telegram.tenant_secret' => self::SECRET]);

        $this->postJson('/api/telegram/webhook', ['update_id' => 1])->assertStatus(401);
    }

    public function test_con_un_secret_incorrecto_el_update_se_rechaza(): void
    {
        config(['telegram.tenant_secret' => self::SECRET]);

        $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'otro-secreto')
            ->postJson('/api/telegram/webhook', ['update_id' => 1])
            ->assertStatus(401);
    }

    public function test_con_el_secret_correcto_el_middleware_deja_pasar(): void
    {
        config(['telegram.tenant_secret' => self::SECRET]);

        $request = Request::create('/api/telegram/webhook', 'POST');
        $request->headers->set('X-Telegram-Bot-Api-Secret-Token', self::SECRET);

        $paso = false;
        (new VerifyTelegramTenant)->handle($request, function () use (&$paso) {
            $paso = true;

            return response('ok');
        });

        $this->assertTrue($paso, 'Con el secret correcto el middleware tiene que dejar pasar el update.');
    }

    public function test_el_middleware_sigue_aplicado_a_la_ruta_del_webhook(): void
    {
        $ruta = collect(Route::getRoutes())->first(fn ($r) => $r->uri() === 'api/telegram/webhook');

        $this->assertNotNull($ruta, 'No existe la ruta api/telegram/webhook.');
        $this->assertContains(
            'verify.telegram.tenant',
            $ruta->middleware(),
            'El webhook del bot tenant quedó sin verificar: cualquiera puede forjar un update.'
        );
    }
}
