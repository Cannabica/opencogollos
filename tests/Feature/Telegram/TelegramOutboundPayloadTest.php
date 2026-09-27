<?php

namespace Tests\Feature\Telegram;

use App\Models\Tenant;
use App\Services\Admin\AdminNotifierService;
use App\Telegram\Admin\Commands\StartCommand as AdminStartCommand;
use Telegram\Bot\Api;
use Telegram\Bot\BotsManager;
use Telegram\Bot\Objects\Update;
use Tests\Support\FakeTelegramHttpClient;
use Tests\TestCase;

/**
 * Qué payload SALE de la app por Telegram, sin red y sin mocks del SDK.
 *
 * Antes el único test de avisos salientes (`tests/Unit/Admin/AdminNotifierServiceTest.php`) verificaba
 * CANTIDAD de envíos (o que no explotara), no el contenido: un `sendMessage` con el chat equivocado o
 * un texto vacío pasaba verde. Acá el `Api` real del SDK corre completo y lo único simulado es el
 * transporte (`FakeTelegramHttpClient` inyectado como `config('telegram.http_client_handler')`), así
 * que se puede afirmar chat_id, texto, parse_mode y —clave— contra qué base URL salió el request.
 *
 * El `BotsManager` es `final`: no se puede mockear ni vía facade. Por eso se instancia uno real con el
 * config de test y se lo reemplaza en el contenedor (el provider del SDK lo registra como singleton).
 */
class TelegramOutboundPayloadTest extends TestCase
{
    private const ADMIN_CHAT_ID = 812714520;

    private const BASE_BOT_URL = 'http://emulador.test/bot';

    /**
     * Instala el transporte falso y devuelve el capturador.
     */
    private function fakeTelegram(): FakeTelegramHttpClient
    {
        $fake = new FakeTelegramHttpClient;

        config([
            'telegram.http_client_handler' => $fake,
            'telegram.base_bot_url' => self::BASE_BOT_URL,
            'telegram.bots.default.token' => 'emulador-cultivador',
            'telegram.bots.admin.token' => 'emulador-admin',
        ]);

        $this->app->instance(BotsManager::class, new BotsManager(config('telegram')));

        return $fake;
    }

    public function test_el_aviso_al_superadmin_sale_con_chat_texto_y_parse_mode(): void
    {
        $fake = $this->fakeTelegram();
        config(['telegram.admin_allowed_user_ids' => self::ADMIN_CHAT_ID . ',812714521']);

        $sent = app(AdminNotifierService::class)->notify('<b>hola</b>');

        $this->assertSame(2, $sent);

        $calls = $fake->callsFor('sendMessage');
        $this->assertCount(2, $calls);

        $this->assertSame(self::ADMIN_CHAT_ID, (int) $calls[0]['params']['chat_id']);
        $this->assertSame('<b>hola</b>', (string) $calls[0]['params']['text']);
        $this->assertSame('HTML', (string) $calls[0]['params']['parse_mode']);

        $this->assertSame(812714521, (int) $calls[1]['params']['chat_id']);
    }

    public function test_el_aviso_de_tenant_nuevo_lleva_nombre_email_e_id_para_activarlo(): void
    {
        $fake = $this->fakeTelegram();
        config(['telegram.admin_allowed_user_ids' => (string) self::ADMIN_CHAT_ID]);

        // OJO: `id` NO está en $fillable de Tenant, así que pasarlo por el constructor se descarta en
        // silencio (el test viejo de AdminNotifierService lo hacía y por eso sólo podía afirmar "0").
        $tenant = new Tenant(['name' => 'Prueba SA', 'email' => 'p@example.com']);
        $tenant->id = 7;

        $this->assertSame(1, AdminNotifierService::notifyNewTenant($tenant));

        $text = $fake->lastText();
        $this->assertStringContainsString('Prueba SA', $text);
        $this->assertStringContainsString('p@example.com', $text);
        $this->assertStringContainsString('/activar 7', $text);
    }

    public function test_base_bot_url_apunta_el_request_al_emulador(): void
    {
        $fake = $this->fakeTelegram();
        config(['telegram.admin_allowed_user_ids' => (string) self::ADMIN_CHAT_ID]);

        app(AdminNotifierService::class)->notify('hola');

        $call = $fake->lastCall();
        $this->assertNotNull($call);

        // El SDK arma la URL como base_bot_url . TOKEN . '/' . metodo
        // (TelegramClient::prepareRequest). Con el emulador en 8082 esto es
        // http://127.0.0.1:8082/bot<TOKEN>/sendMessage.
        $this->assertSame(self::BASE_BOT_URL . 'emulador-admin/sendMessage', $call['url']);
        $this->assertSame('emulador-admin', $call['token']);
        $this->assertSame('sendMessage', $call['endpoint']);
    }

    public function test_una_respuesta_de_comando_sale_por_el_transporte_emulado(): void
    {
        $fake = $this->fakeTelegram();
        config(['telegram.admin_allowed_user_ids' => (string) self::ADMIN_CHAT_ID]);

        // Api real del SDK, transporte falso: el comando contesta de verdad.
        $api = new Api('emulador-admin', false, $fake, self::BASE_BOT_URL);

        $update = new Update([
            'update_id' => 400,
            'message' => [
                'message_id' => 1,
                'date' => time(),
                'text' => '/start',
                'from' => ['id' => self::ADMIN_CHAT_ID, 'is_bot' => false, 'first_name' => 'Tester'],
                'chat' => ['id' => self::ADMIN_CHAT_ID, 'type' => 'private', 'first_name' => 'Tester'],
            ],
        ]);

        $command = new AdminStartCommand;
        $command->make($api, $update, ['offset' => 0, 'length' => 6, 'type' => 'bot_command']);

        $this->assertTrue($fake->hasEndpoint('sendMessage'), 'El comando no contestó por el transporte.');
        $this->assertStringContainsString('bot de administración', $fake->lastText());
        $this->assertSame(self::ADMIN_CHAT_ID, (int) $fake->lastCall()['params']['chat_id']);
    }

    public function test_un_comando_de_un_usuario_no_autorizado_recibe_el_rechazo_y_no_corre(): void
    {
        $fake = $this->fakeTelegram();
        config(['telegram.admin_allowed_user_ids' => (string) self::ADMIN_CHAT_ID]);

        $api = new Api('emulador-admin', false, $fake, self::BASE_BOT_URL);

        $update = new Update([
            'update_id' => 401,
            'message' => [
                'message_id' => 1,
                'date' => time(),
                'text' => '/start',
                'from' => ['id' => 999999, 'is_bot' => false, 'first_name' => 'Intruso'],
                'chat' => ['id' => 999999, 'type' => 'private', 'first_name' => 'Intruso'],
            ],
        ]);

        $command = new AdminStartCommand;
        $command->make($api, $update, ['offset' => 0, 'length' => 6, 'type' => 'bot_command']);

        // AdminCommand::ensureAuthorized() contesta el rechazo y devuelve false, así que el
        // handle() del comando real no llega a correr.
        $this->assertTrue($fake->hasEndpoint('sendMessage'));
        $this->assertStringContainsString('No estás autorizado', $fake->lastText());
        $this->assertStringNotContainsString('bot de administración', $fake->lastText());
    }

    public function test_si_el_envio_falla_se_loguea_y_no_se_propaga(): void
    {
        $fake = $this->fakeTelegram();
        config(['telegram.admin_allowed_user_ids' => (string) self::ADMIN_CHAT_ID]);

        $fake->failWith('Bad Request: chat not found');

        $this->assertSame(0, app(AdminNotifierService::class)->notify('hola'));
    }
}
