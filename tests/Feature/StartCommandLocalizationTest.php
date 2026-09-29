<?php

namespace Tests\Feature;

use App\Telegram\Admin\Commands\StartCommand as AdminStartCommand;
use App\Telegram\Commands\StartCommand as TenantStartCommand;
use Mockery;
use Mockery\MockInterface;
use Telegram\Bot\Api;
use Telegram\Bot\Objects\Message;
use Telegram\Bot\Objects\Update;
use Tests\TestCase;

/**
 * Localización de los textos de /start de los bots (épica open-core, WS4 · T4.4).
 *
 * Regla: con config('platform.*') en null, el bot no menciona dominio, plataforma,
 * monitor ni comunidad de ninguna instalación. Con las vars seteadas, el texto
 * vuelve a ser el de siempre (mismo armado por líneas).
 */
class StartCommandLocalizationTest extends TestCase
{
    private const ADMIN_USER_ID = 812714520;

    private const BRAND = 'MiMarca';

    /** @var list<array<string, mixed>> */
    private array $replies = [];

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @return Api&MockInterface */
    private function apiMock(): Api
    {
        $api = Mockery::mock(Api::class);
        $api->shouldReceive('sendMessage')->andReturnUsing(function (array $params): Message {
            $this->replies[] = $params;

            return new Message(['message_id' => 1, 'chat' => ['id' => $params['chat_id'] ?? 0, 'type' => 'private']]);
        });

        return $api;
    }

    /**
     * @param  class-string  $commandClass
     */
    private function runStart(string $commandClass, int $fromId = self::ADMIN_USER_ID): string
    {
        $this->replies = [];
        $text = '/start';

        $update = new Update([
            'update_id' => 100,
            'message' => [
                'message_id' => 1,
                'date' => time(),
                'text' => $text,
                'from' => ['id' => $fromId, 'is_bot' => false, 'first_name' => 'Tester'],
                'chat' => ['id' => $fromId, 'type' => 'private', 'first_name' => 'Tester'],
            ],
        ]);

        $command = new $commandClass();
        $command->make($this->apiMock(), $update, ['offset' => 0, 'length' => strlen($text), 'type' => 'bot_command']);

        // El array lo llena el mock de `sendMessage` (PHPStan no sigue el callback), asi que el tipo
        // se declara en la variable local: es la forma de que `[0]['text']` no sea un offset sobre `array{}`.
        /** @var array<int, array<string, mixed>> $replies */
        $replies = $this->replies;

        return $replies[0]['text'] ?? '';
    }

    private function configurePlatform(): void
    {
        config([
            'platform.brand_name' => self::BRAND,
            'platform.site_url' => 'https://mimarca.ar',
            'platform.status_page_url' => 'https://status.mimarca.ar/',
            'platform.platform_url' => 'https://plataforma.mimarca.ar',
            'platform.community.discord_url' => 'https://discord.gg/mimarca',
        ]);
    }

    public function test_tenant_start_sin_config_no_menciona_dominio_plataforma_ni_comunidad(): void
    {
        $text = $this->runStart(TenantStartCommand::class);

        $this->assertStringNotContainsString('mimarca', $text);
        $this->assertStringNotContainsString('http', $text);
        $this->assertStringNotContainsString('discord', $text);
        $this->assertStringNotContainsString('status page', $text);
        // El instructivo genérico se mantiene.
        $this->assertStringContainsString('/auth MITOKEN', $text);
        $this->assertStringContainsString('/repetirriego', $text);
    }

    public function test_tenant_start_con_config_muestra_los_bloques_configurados(): void
    {
        $this->configurePlatform();

        $text = $this->runStart(TenantStartCommand::class);

        $this->assertStringContainsString('tu cuenta en mimarca.ar', $text);
        $this->assertStringContainsString('dentro de plataforma.mimarca.ar/tenant', $text);
        $this->assertStringContainsString('status page:', $text);
        $this->assertStringContainsString('https://status.mimarca.ar/', $text);
        $this->assertStringContainsString('server de discord:', $text);
        $this->assertStringContainsString('https://discord.gg/mimarca', $text);
    }

    public function test_admin_start_sin_config_se_presenta_solo_con_el_producto(): void
    {
        config(['telegram.admin_allowed_user_ids' => (string) self::ADMIN_USER_ID]);

        $text = $this->runStart(AdminStartCommand::class);

        $this->assertStringContainsString('bot de administración de OpenCogollos.', $text);
        $this->assertStringNotContainsString('mimarca', $text);
        $this->assertStringNotContainsString('de  / OpenCogollos', $text);
    }

    public function test_admin_start_con_config_muestra_la_marca(): void
    {
        config([
            'telegram.admin_allowed_user_ids' => (string) self::ADMIN_USER_ID,
            'platform.brand_name' => self::BRAND,
        ]);

        $text = $this->runStart(AdminStartCommand::class);

        $this->assertStringContainsString('bot de administración de ' . self::BRAND . ' / OpenCogollos.', $text);
    }
}
