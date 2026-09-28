<?php

namespace Tests\Feature\Telegram;

use App\Models\Action;
use App\Models\ActionType;
use App\Models\Indoor;
use App\Models\Plant;
use App\Models\Scopes\TenantScope;
use App\Models\Seed;
use App\Models\Tenant;
use App\Models\TelegramUserTenant;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Telegram\Bot\BotsManager;
use Telegram\Bot\Objects\Update;
use Tests\Support\FakeTelegramHttpClient;
use Tests\TestCase;

/**
 * ACCESO CRUZADO ENTRE GRUPOS POR EL BOT DE TELEGRAM (hotfix 2026-09-28).
 *
 * El webhook NO tiene sesión, así que `auth()->user()` es null y el `TenantScope` no filtraba nada:
 * `/actiondetails`, `/plantdetails` y los callbacks `select_indoor:` / `select_plants:` /
 * `repeat_irrigation:` / `confirm_observation:` devolvían (y escribían sobre) datos de otro grupo con
 * sólo cambiar el id. Acá se fija el comportamiento en LAS DOS DIRECCIONES, como en
 * `TenantIsolationTest`: el grupo no ve lo ajeno Y sigue viendo lo propio.
 *
 * Corre sin red con el doble de transporte (`FakeTelegramHttpClient`): el `Api` y `sendMessage()`
 * reales corren, sólo se simula el HTTP.
 */
class AislamientoTenantsBotTest extends TestCase
{
    use RefreshDatabase;

    private const CHAT_A = 111111111;

    private const CHAT_B = 222222222;

    private FakeTelegramHttpClient $fake;

    private ActionType $riego;

    protected function setUp(): void
    {
        parent::setUp();

        // El contexto es estático y sobrevive entre tests: se limpia siempre.
        TenantContext::forget();

        $this->fake = new FakeTelegramHttpClient;

        config([
            'telegram.http_client_handler' => $this->fake,
            'telegram.base_bot_url' => 'http://emulador.test/bot',
            'telegram.bots.default.token' => 'emulador-cultivador',
        ]);

        // `BotsManager` es final: se reemplaza por uno real apuntado al transporte falso.
        $this->app->instance(BotsManager::class, new BotsManager(config('telegram')));

        // Catálogo compartido: `repeat_irrigation` sólo acepta acciones del tipo 1.
        $this->riego = ActionType::create([
            'name' => 'Riego',
            'action_class' => 'App\\Utilities\\PlantActions\\RegisterState',
        ]);
    }

    protected function tearDown(): void
    {
        TenantContext::forget();

        parent::tearDown();
    }

    /** Un grupo completo (indoor, semilla, planta, acción de riego) con su chat de Telegram asociado. */
    private function grupo(string $nombre, int $chatId): array
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

        $accion = Action::create([
            'action_date' => now()->toDateString(),
            'indoor_id' => $indoor->id,
            'action_type_id' => $this->riego->id,
            'tenant_id' => $tenant->id,
            'data' => ['irrigation' => ['irrigation_type' => 'liters', 'liters' => 3]],
        ]);
        $accion->plants()->attach($plant->id);

        TelegramUserTenant::create([
            'telegram_user_id' => $chatId,
            'tenant_id' => $tenant->id,
            'telegram_username' => 'chat-'.$nombre,
            'expires_at' => now()->addDay(),
        ]);

        return compact('tenant', 'indoor', 'plant', 'accion');
    }

    private function updateDeTexto(string $texto, int $chatId): Update
    {
        // Telegram manda la entidad `bot_command` cubriendo SÓLO el nombre del comando: si falta (o si
        // cubre todo el texto), el SDK no resuelve el comando y los argumentos llegan vacíos.
        $nombre = (string) strtok($texto, ' ');

        return new Update([
            'update_id' => 1,
            'message' => [
                'message_id' => 10,
                'date' => time(),
                'text' => $texto,
                'entities' => [[
                    'offset' => 0,
                    'length' => strlen($nombre),
                    'type' => 'bot_command',
                ]],
                'from' => ['id' => $chatId, 'is_bot' => false, 'first_name' => 'Tester'],
                'chat' => ['id' => $chatId, 'type' => 'private', 'first_name' => 'Tester'],
            ],
        ]);
    }

    private function updateDeCallback(string $data, int $chatId, string $textoDelMensaje = '🏠 *Indoor seleccionado:* INDOOR-A'): Update
    {
        return new Update([
            'update_id' => 2,
            'callback_query' => [
                'id' => 'callback-1',
                'from' => ['id' => $chatId, 'is_bot' => false, 'first_name' => 'Tester'],
                'chat_instance' => '1',
                'data' => $data,
                'message' => [
                    'message_id' => 11,
                    'date' => time(),
                    'chat' => ['id' => $chatId, 'type' => 'private'],
                    'text' => $textoDelMensaje,
                ],
            ],
        ]);
    }

    /** Corre el comando como lo hace el webhook (mismo `triggerCommand` del SDK, con el transporte falso). */
    private function correrComando(string $nombre, Update $update): void
    {
        app(BotsManager::class)->bot('default')->triggerCommand($nombre, $update);
    }

    /** Todo lo que el bot contestó (sendMessage, editMessageText y alertas de callback). */
    private function respuestas(): string
    {
        return implode("\n", $this->fake->texts());
    }

    private function accionesTotales(): int
    {
        return Action::withoutGlobalScope(TenantScope::class)->count();
    }

    // ------------------------------------------------------------------
    // /actiondetails
    // ------------------------------------------------------------------

    public function test_un_grupo_no_puede_ver_la_accion_de_otro_grupo(): void
    {
        $this->grupo('A', self::CHAT_A);
        $b = $this->grupo('B', self::CHAT_B);

        $texto = '/actiondetails '.$b['accion']->id;
        $this->correrComando('actiondetails', $this->updateDeTexto($texto, self::CHAT_A));

        $respuesta = $this->respuestas();
        $this->assertStringContainsString('no encontrada', $respuesta);
        $this->assertStringNotContainsString('INDOOR-B', $respuesta, 'el bot mostró el indoor de otro grupo');
        $this->assertStringNotContainsString('PLANTA-B', $respuesta, 'el bot mostró la planta de otro grupo');
    }

    public function test_un_grupo_si_ve_su_propia_accion(): void
    {
        $a = $this->grupo('A', self::CHAT_A);
        $this->grupo('B', self::CHAT_B);

        $texto = '/actiondetails '.$a['accion']->id;
        $this->correrComando('actiondetails', $this->updateDeTexto($texto, self::CHAT_A));

        $respuesta = $this->respuestas();
        $this->assertStringContainsString('INDOOR-A', $respuesta, 'el bot no le mostró su propio indoor');
        $this->assertStringContainsString('PLANTA-A', $respuesta, 'el bot no le mostró su propia planta');
    }

    // ------------------------------------------------------------------
    // /plantdetails
    // ------------------------------------------------------------------

    public function test_un_grupo_no_puede_ver_la_planta_ni_el_historial_de_otro_grupo(): void
    {
        $this->grupo('A', self::CHAT_A);
        $b = $this->grupo('B', self::CHAT_B);

        $texto = '/plantdetails '.$b['plant']->id;
        $this->correrComando('plantdetails', $this->updateDeTexto($texto, self::CHAT_A));

        $respuesta = $this->respuestas();
        $this->assertStringContainsString('no encontrada', $respuesta);
        $this->assertStringNotContainsString('PLANTA-B', $respuesta, 'el bot mostró la planta de otro grupo');
        $this->assertStringNotContainsString('INDOOR-B', $respuesta, 'el bot mostró el indoor de otro grupo');
        $this->assertStringNotContainsString('Riego', $respuesta, 'el bot mostró el historial de acciones de otro grupo');
    }

    public function test_un_grupo_si_ve_su_propia_planta_y_su_historial(): void
    {
        $a = $this->grupo('A', self::CHAT_A);
        $this->grupo('B', self::CHAT_B);

        $texto = '/plantdetails '.$a['plant']->id;
        $this->correrComando('plantdetails', $this->updateDeTexto($texto, self::CHAT_A));

        $respuesta = $this->respuestas();
        $this->assertStringContainsString('PLANTA-A', $respuesta, 'el bot no le mostró su propia planta');
        $this->assertStringContainsString('INDOOR-A', $respuesta, 'el bot no le mostró su propio indoor');
        $this->assertStringNotContainsString('PLANTA-B', $respuesta);
    }

    // ------------------------------------------------------------------
    // Mismo comando, camino callback (los botones inline): la ruta despacha
    // `actiondetails:<id>` / `plantdetails:<id>` al mismo handle().
    // ------------------------------------------------------------------

    public function test_un_grupo_no_puede_ver_la_accion_de_otro_grupo_por_callback(): void
    {
        $this->grupo('A', self::CHAT_A);
        $b = $this->grupo('B', self::CHAT_B);

        $this->correrComando(
            'actiondetails',
            $this->updateDeCallback('actiondetails:'.$b['accion']->id, self::CHAT_A)
        );

        $respuesta = $this->respuestas();
        $this->assertStringContainsString('no encontrada', $respuesta);
        $this->assertStringNotContainsString('PLANTA-B', $respuesta, 'el bot mostró la planta de otro grupo');
        $this->assertStringNotContainsString('INDOOR-B', $respuesta, 'el bot mostró el indoor de otro grupo');
    }

    public function test_un_grupo_no_puede_ver_la_planta_de_otro_grupo_por_callback(): void
    {
        $this->grupo('A', self::CHAT_A);
        $b = $this->grupo('B', self::CHAT_B);

        $this->correrComando(
            'plantdetails',
            $this->updateDeCallback('plantdetails:'.$b['plant']->id, self::CHAT_A)
        );

        $respuesta = $this->respuestas();
        $this->assertStringContainsString('no encontrada', $respuesta);
        $this->assertStringNotContainsString('PLANTA-B', $respuesta, 'el bot mostró la planta de otro grupo');
    }

    // ------------------------------------------------------------------
    // callback repeat_irrigation
    // ------------------------------------------------------------------

    public function test_no_se_puede_repetir_el_riego_de_otro_grupo(): void
    {
        $this->assertSame(1, $this->riego->id, 'el tipo de acción 1 tiene que ser "Riego" para este test');

        $this->grupo('A', self::CHAT_A);
        $b = $this->grupo('B', self::CHAT_B);

        $antes = $this->accionesTotales();

        $this->correrComando(
            'callback',
            $this->updateDeCallback('repeat_irrigation:'.$b['accion']->id, self::CHAT_A)
        );

        $this->assertStringContainsString('No se pudo encontrar el riego original', $this->respuestas());
        $this->assertSame($antes, $this->accionesTotales(), 'se creó una acción a partir del riego de otro grupo');
    }

    public function test_si_puede_repetir_su_propio_riego(): void
    {
        $this->assertSame(1, $this->riego->id, 'el tipo de acción 1 tiene que ser "Riego" para este test');

        $a = $this->grupo('A', self::CHAT_A);
        $this->grupo('B', self::CHAT_B);

        $antes = $this->accionesTotales();

        $this->correrComando(
            'callback',
            $this->updateDeCallback('repeat_irrigation:'.$a['accion']->id, self::CHAT_A)
        );

        $this->assertStringContainsString('Riego repetido exitosamente', $this->respuestas());
        $this->assertSame($antes + 1, $this->accionesTotales(), 'no se creó la acción del riego repetido');
    }

    // ------------------------------------------------------------------
    // callbacks del flujo de observación con foto
    // ------------------------------------------------------------------

    public function test_no_se_puede_listar_las_plantas_de_un_indoor_ajeno(): void
    {
        $this->grupo('A', self::CHAT_A);
        $b = $this->grupo('B', self::CHAT_B);

        $this->correrComando(
            'callback',
            $this->updateDeCallback('select_indoor:'.$b['indoor']->id, self::CHAT_A)
        );

        $respuesta = $this->respuestas();
        $this->assertStringContainsString('No se encontró el indoor', $respuesta);
        $this->assertStringNotContainsString('PLANTA-B', $respuesta, 'el bot listó plantas de otro grupo');
    }

    public function test_no_se_puede_seleccionar_una_planta_ajena(): void
    {
        $this->grupo('A', self::CHAT_A);
        $b = $this->grupo('B', self::CHAT_B);

        $this->correrComando(
            'callback',
            $this->updateDeCallback('select_plants:'.$b['plant']->id, self::CHAT_A)
        );

        $respuesta = $this->respuestas();
        $this->assertStringContainsString('No se encontró la planta', $respuesta);
        $this->assertStringNotContainsString('PLANTA-B', $respuesta, 'el bot mostró la planta de otro grupo');
    }

    public function test_no_se_puede_confirmar_una_observacion_en_un_indoor_ajeno(): void
    {
        $this->grupo('A', self::CHAT_A);
        $b = $this->grupo('B', self::CHAT_B);

        // El indoor del callback sale del `callback_data` (lo elige el usuario): con el cache cargado, el
        // único freno es el cruce contra el tenant de la asociación.
        cache()->put('observation_step_'.self::CHAT_A, [
            'indoor_id' => $b['indoor']->id,
            'photo_info' => ['file_id' => 'foto-1', 'is_album' => false],
            'step' => 'select_plants',
        ], now()->addHour());

        $antes = $this->accionesTotales();

        $this->correrComando(
            'callback',
            $this->updateDeCallback(
                'confirm_observation:'.$b['indoor']->id.':'.urlencode('prueba'),
                self::CHAT_A
            )
        );

        $this->assertStringContainsString('No se encontró el indoor', $this->respuestas());
        $this->assertSame($antes, $this->accionesTotales(), 'se creó una acción dentro del indoor de otro grupo');
    }
}
