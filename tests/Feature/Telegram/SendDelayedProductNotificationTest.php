<?php

namespace Tests\Feature\Telegram;

use App\Jobs\SendDelayedProductNotification;
use App\Models\Tenant;
use App\Models\TelegramUserTenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Telegram\Bot\BotsManager;
use Tests\Support\FakeTelegramHttpClient;
use Tests\TestCase;

/**
 * El recordatorio de aplicación de producto (flujo SALIENTE real de la app): antes no tenía ningún test
 * que mirara el mensaje de Telegram; sólo se cubría el mail/Filament notification del mismo job.
 *
 * El job resuelve los chats por `telegram_user_tenant` (el chat se vincula al TENANT, no al usuario) y
 * manda con `Telegram::sendMessage([...])` al bot default. Acá se verifica el payload real (chat_id y
 * texto) contra el transporte falso, sin red.
 */
class SendDelayedProductNotificationTest extends TestCase
{
    use RefreshDatabase;

    private const TELEGRAM_CHAT_ID = 812714520;

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

    public function test_el_recordatorio_de_aplicacion_llega_al_chat_vinculado_al_tenant(): void
    {
        $fake = $this->fakeTelegram();

        $tenant = Tenant::factory()->create();
        User::factory()->create(['tenant_id' => $tenant->id]);

        TelegramUserTenant::create([
            'telegram_user_id' => self::TELEGRAM_CHAT_ID,
            'tenant_id' => $tenant->id,
            'telegram_username' => 'cultivador',
        ]);

        (new SendDelayedProductNotification('flora', 3, $tenant->id))->handle();

        $calls = $fake->callsFor('sendMessage');
        $this->assertCount(1, $calls, 'Se esperaba exactamente un mensaje de Telegram.');

        $this->assertSame(self::TELEGRAM_CHAT_ID, (int) $calls[0]['params']['chat_id']);

        $text = (string) $calls[0]['params']['text'];
        $this->assertStringContainsString('Es momento de aplicar', $text);
        $this->assertStringContainsString('flora', $text);
        $this->assertStringContainsString('3 plantas', $text);
        $this->assertSame('Markdown', (string) $calls[0]['params']['parse_mode']);
        $this->assertSame('emulador-cultivador', $calls[0]['token']);
    }

    public function test_sin_chats_vinculados_no_sale_ningun_mensaje(): void
    {
        $fake = $this->fakeTelegram();

        $tenant = Tenant::factory()->create();
        User::factory()->create(['tenant_id' => $tenant->id]);

        (new SendDelayedProductNotification('flora', 3, $tenant->id))->handle();

        $this->assertSame(0, $fake->callCount(), 'No debería haber llamado a la API de Telegram.');
    }

    public function test_un_chat_de_otro_tenant_no_recibe_el_recordatorio(): void
    {
        $fake = $this->fakeTelegram();

        $tenant = Tenant::factory()->create();
        $otroTenant = Tenant::factory()->create();

        User::factory()->create(['tenant_id' => $tenant->id]);

        TelegramUserTenant::create([
            'telegram_user_id' => self::TELEGRAM_CHAT_ID,
            'tenant_id' => $otroTenant->id,
            'telegram_username' => 'ajeno',
        ]);

        (new SendDelayedProductNotification('flora', 3, $tenant->id))->handle();

        $this->assertSame(0, $fake->callCount(), 'El aviso se filtró a un chat de otro tenant.');
    }
}
