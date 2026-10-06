<?php

namespace Tests\Feature;

use App\Jobs\SendDelayedProductNotification;
use App\Models\Action;
use App\Models\ActionType;
use App\Models\ApiToken;
use App\Models\Indoor;
use App\Models\Plant;
use App\Models\Seed;
use App\Models\Tenant;
use App\Models\TelegramUserTenant;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Telegram\Bot\BotsManager;
use Tests\Support\FakeTelegramHttpClient;
use Tests\Support\SondaDeContexto;
use Tests\TestCase;

/**
 * LOS CAMINOS QUE CORREN SIN SESIÓN Y SU REGLA DE CONTEXTO (2026-09-29).
 *
 * El `TenantScope` falla cerrado cuando no hay contexto, y eso deja tres caminos con reglas distintas:
 *
 *   1. **API con token** → el grupo sale del token y lo fija `TenantTokenMiddleware`. Antes NO lo fijaba:
 *      con el scope cerrado, `GET /api/plants` devolvía una lista VACÍA y `show($id)` un 404, aunque el
 *      token fuera válido (la API quedó muda desde el hotfix del aislamiento).
 *   2. **Job de cola** → CERRADO salvo que el job fije su grupo. El worker corre por consola, así que la
 *      rama de consola (que sigue abierta para seeders/artisan/cron) lo dejaba ABIERTO: un `find($id)`
 *      adentro de un job leía la fila de cualquier grupo. Eso es lo que se cierra acá.
 *   3. **Consola de verdad** (seeders, artisan, cron) → abierta, como antes.
 *
 * ⚠️ POR QUÉ LOS TESTS DE JOB SIMULAN UN PROCESO CLI: `Application::runningUnitTests()` mira el binding
 * `env`, y en la suite el contexto se ve cerrado a propósito (es lo que permite que los demás tests
 * afirmen la falla cerrada). Con ese default, un test de job pasaría IGUAL con o sin el fix (verificado:
 * sacando la rama del job, la suite seguía verde) ⇒ no mediría nada. `comoProcesoCli()` pone `env` fuera
 * de `testing` para que el resolver recorra el mismo camino que recorre un `php artisan` real.
 */
class TenantContextPathsTest extends TestCase
{
    use RefreshDatabase;

    private const CHAT_A = 812714520;

    private ActionType $riego;

    /** @var array{tenant: Tenant, indoor: Indoor, seed: Seed, plant: Plant} */
    private array $a;

    /** @var array{tenant: Tenant, indoor: Indoor, seed: Seed, plant: Plant} */
    private array $b;

    protected function setUp(): void
    {
        parent::setUp();

        SondaDeContexto::reset();

        $this->riego = ActionType::create([
            'name' => 'RIEGO',
            'tenant_id' => null,
            'action_class' => 'App\Utilities\PlantActions\RegisterState',
        ]);

        $this->a = $this->grupo('A');
        $this->b = $this->grupo('B');
    }

    /** Un grupo completo (indoor + semilla + planta) para poder contar y comparar. */
    private function grupo(string $nombre): array
    {
        $tenant = Tenant::factory()->create(['name' => 'GRUPO-'.$nombre, 'active' => true]);

        $indoor = Indoor::create([
            'name' => 'INDOOR-'.$nombre, 'large' => 2, 'width' => 2, 'height' => 2,
            'tenant_id' => $tenant->id, 'fans' => ['f1'], 'lamps' => ['l1'],
        ]);

        $seed = Seed::create([
            'name' => 'SEMILLA-'.$nombre, 'tenant_id' => $tenant->id, 'seed_type' => 'auto',
            'flowering_time' => 60, 'ratio_thc' => 20, 'ratio_cbd' => 1,
        ]);

        $plant = Plant::create([
            'name' => 'PLANTA-'.$nombre, 'seed_id' => $seed->id, 'indoor_id' => $indoor->id,
            'state' => 'Vegetativa', 'flowerpot' => '11L', 'capacity' => 11,
        ]);

        return compact('tenant', 'indoor', 'seed', 'plant');
    }

    /** Token de API vigente para un grupo; devuelve el token en claro (el hash es lo que se guarda). */
    private function tokenDe(Tenant $tenant): string
    {
        $token = 'token-de-prueba-'.$tenant->id;

        ApiToken::create([
            'tenant_id' => $tenant->id,
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addDay(),
            'reference' => 'test',
        ]);

        return $token;
    }

    /** Corre las aserciones con el resolver viendo el camino de un proceso CLI real. */
    private function comoProcesoCli(callable $assercciones): void
    {
        app()->instance('env', 'production');

        try {
            $assercciones();
        } finally {
            app()->instance('env', 'testing');
        }
    }

    private function fakeTelegram(): FakeTelegramHttpClient
    {
        $fake = new FakeTelegramHttpClient;

        config([
            'telegram.http_client_handler' => $fake,
            'telegram.base_bot_url' => 'http://emulador.test/bot',
            'telegram.bots.default.token' => 'emulador-cultivador',
            'telegram.bots.admin.token' => 'emulador-admin',
        ]);

        $this->app->instance(BotsManager::class, new BotsManager(config('telegram')));

        return $fake;
    }

    private function accionDe(array $grupo, string $nota): Action
    {
        return Action::create([
            'action_date' => now()->toDateString(),
            'indoor_id' => $grupo['indoor']->id,
            'action_type_id' => $this->riego->id,
            'tenant_id' => $grupo['tenant']->id,
            'data' => ['product_application' => ['application_type' => 'flora', 'observations' => $nota]],
        ]);
    }

    // ------------------------------------------------------------------ API

    public function test_la_api_con_token_ve_lo_su_grupo_y_no_lo_ajeno(): void
    {
        $token = $this->tokenDe($this->a['tenant']);

        $res = $this->withHeader('Authorization', 'Bearer '.$token)->getJson('/api/plants');

        $res->assertOk();
        $this->assertCount(1, $res->json(), 'La API tiene que devolver SOLO la planta del grupo del token');
        $this->assertSame('PLANTA-A', $res->json()[0]['name']);
    }

    public function test_la_api_sin_token_no_devuelve_nada(): void
    {
        $this->getJson('/api/plants')->assertStatus(401);
    }

    public function test_la_api_no_abre_una_planta_de_otro_grupo(): void
    {
        $token = $this->tokenDe($this->a['tenant']);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/plants/'.$this->b['plant']->id)
            ->assertStatus(404);
    }

    // ------------------------------------------------------------------ CONSOLA

    public function test_la_consola_sin_sesion_ve_todo(): void
    {
        $this->comoProcesoCli(function (): void {
            $this->assertNull(
                TenantContext::current(),
                'Un comando artisan real no filtra (seeders, cron y el digest del admin dependen de eso)'
            );
            $this->assertSame(2, Plant::count(), 'La consola ve los datos de todos los grupos');
        });
    }

    // ------------------------------------------------------------------ JOBS

    public function test_un_job_sin_contexto_propio_falla_cerrado(): void
    {
        $this->comoProcesoCli(function (): void {
            dispatch_sync(new SondaDeContexto);

            $this->assertFalse(
                SondaDeContexto::$contexto,
                'El worker corre en consola: si el job no fija su grupo, no puede ver nada'
            );
            $this->assertSame(0, SondaDeContexto::$plantas, 'Ninguna planta de ningún grupo');
        });
    }

    public function test_un_job_que_fija_su_grupo_ve_lo_suyo(): void
    {
        $this->comoProcesoCli(function (): void {
            dispatch_sync(new SondaDeContexto($this->a['tenant']->id));

            $this->assertSame((int) $this->a['tenant']->id, SondaDeContexto::$contexto);
            $this->assertSame(1, SondaDeContexto::$plantas, 'Ve la planta de SU grupo y nada más');
        });
    }

    public function test_el_contexto_de_un_job_no_pasa_al_siguiente(): void
    {
        $this->comoProcesoCli(function (): void {
            dispatch_sync(new SondaDeContexto($this->a['tenant']->id));
            $this->assertSame(1, SondaDeContexto::$plantas);

            SondaDeContexto::reset();
            dispatch_sync(new SondaDeContexto);

            $this->assertFalse(
                SondaDeContexto::$contexto,
                'El job siguiente no puede heredar el grupo del anterior'
            );
            $this->assertSame(0, SondaDeContexto::$plantas);
        });
    }

    public function test_el_recordatorio_ve_la_accion_de_su_grupo(): void
    {
        $fake = $this->fakeTelegram();

        User::factory()->create(['tenant_id' => $this->a['tenant']->id]);
        TelegramUserTenant::create([
            'telegram_user_id' => self::CHAT_A,
            'tenant_id' => $this->a['tenant']->id,
            'telegram_username' => 'cultivador-a',
        ]);

        $accionPropia = $this->accionDe($this->a, 'nota de A');

        $this->comoProcesoCli(function () use ($accionPropia, $fake): void {
            dispatch_sync(new SendDelayedProductNotification('flora', 3, $this->a['tenant']->id, $accionPropia->id));

            $calls = $fake->callsFor('sendMessage');
            $this->assertCount(1, $calls, 'El recordatorio del grupo A tiene que salir');

            $texto = (string) $calls[0]['params']['text'];
            $this->assertStringContainsString('INDOOR-A', $texto, 'Tiene que resolver SU acción (necesita el contexto)');
            $this->assertStringContainsString('nota de A', $texto);
        });
    }

    public function test_el_recordatorio_no_lee_la_accion_de_otro_grupo(): void
    {
        $fake = $this->fakeTelegram();

        User::factory()->create(['tenant_id' => $this->a['tenant']->id]);
        TelegramUserTenant::create([
            'telegram_user_id' => self::CHAT_A,
            'tenant_id' => $this->a['tenant']->id,
            'telegram_username' => 'cultivador-a',
        ]);

        // La acción es del grupo B y el job corre para el grupo A: no la puede ver.
        $accionAjena = $this->accionDe($this->b, 'nota de B');

        $this->comoProcesoCli(function () use ($accionAjena, $fake): void {
            dispatch_sync(new SendDelayedProductNotification('flora', 3, $this->a['tenant']->id, $accionAjena->id));

            $calls = $fake->callsFor('sendMessage');
            $this->assertCount(1, $calls, 'El aviso sale igual, pero sin datos del otro grupo');

            $texto = (string) $calls[0]['params']['text'];
            $this->assertStringNotContainsString('INDOOR-B', $texto, 'No puede filtrar el indoor de otro grupo');
            $this->assertStringNotContainsString('nota de B', $texto, 'Ni las observaciones de otro grupo');
        });
    }
}
