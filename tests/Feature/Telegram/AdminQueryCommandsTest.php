<?php

namespace Tests\Feature\Telegram;

use App\Models\Tenant;
use App\Models\User;
use App\Telegram\Admin\Commands\EstadoCommand;
use App\Telegram\Admin\Commands\MetricasCommand;
use App\Telegram\Admin\Commands\TenantCommand;
use App\Telegram\Admin\Commands\TenantsCommand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Telegram\Bot\Api;
use Telegram\Bot\Commands\Command;
use Telegram\Bot\Objects\Message;
use Telegram\Bot\Objects\Update;
use Tests\TestCase;

class AdminQueryCommandsTest extends TestCase
{
    use RefreshDatabase;

    private const ADMIN_USER_ID = 812714520;

    /** @var list<array<string, mixed>> */
    private array $replies = [];

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function runCommand(string $commandClass, string $text, int $fromId = self::ADMIN_USER_ID): void
    {
        config(['telegram.admin_allowed_user_ids' => (string) self::ADMIN_USER_ID]);

        $update = new Update([
            'update_id' => 200,
            'message' => [
                'message_id' => 1,
                'date' => time(),
                'text' => $text,
                'from' => ['id' => $fromId, 'is_bot' => false, 'first_name' => 'Frankie'],
                'chat' => ['id' => $fromId, 'type' => 'private', 'first_name' => 'Frankie'],
            ],
        ]);

        $api = Mockery::mock(Api::class);
        $api->shouldReceive('sendMessage')->andReturnUsing(function (array $params): Message {
            $this->replies[] = $params;
            return new Message(['message_id' => 1, 'chat' => ['id' => $params['chat_id'] ?? 0, 'type' => 'private']]);
        });

        /** @var Command $command */
        $command = new $commandClass();
        $command->make($api, $update, ['offset' => 0, 'length' => strlen($text), 'type' => 'bot_command']);
    }

    private function lastReplyText(): string
    {
        $this->assertNotEmpty($this->replies, 'No se envió ninguna respuesta.');
        return (string) end($this->replies)['text'];
    }

    public function test_estado_command_reports_platform_health(): void
    {
        Tenant::factory()->inactive()->create();

        $this->runCommand(EstadoCommand::class, '/estado');

        $text = $this->lastReplyText();
        $this->assertStringContainsString('Estado del sitio', $text);
        $this->assertStringContainsString('Base de datos', $text);
        $this->assertStringContainsString('Jobs fallidos', $text);
        $this->assertStringContainsString('Tenants pendientes de activación: 1', $text);
    }

    public function test_metricas_command_reports_global_counts(): void
    {
        Tenant::factory()->count(2)->create();
        Tenant::factory()->inactive()->create();

        $this->runCommand(MetricasCommand::class, '/metricas');

        $text = $this->lastReplyText();
        $this->assertStringContainsString('Métricas globales', $text);
        $this->assertStringContainsString('Tenants: 3 (2 activos)', $text);
    }

    public function test_tenants_command_lists_tenants(): void
    {
        $tenant = Tenant::factory()->create(['name' => 'Cultivo Test']);
        User::factory()->create(['tenant_id' => $tenant->id]);

        $this->runCommand(TenantsCommand::class, '/tenants');

        $text = $this->lastReplyText();
        $this->assertStringContainsString('Tenants', $text);
        $this->assertStringContainsString("#{$tenant->id}", $text);
        $this->assertStringContainsString('Cultivo Test', $text);
        $this->assertStringContainsString('✅ activo', $text);
    }

    public function test_tenant_command_shows_detail_with_an_id(): void
    {
        $tenant = Tenant::factory()->create(['name' => 'Detalle Bot']);

        $this->runCommand(TenantCommand::class, "/tenant {$tenant->id}");

        $text = $this->lastReplyText();
        $this->assertStringContainsString("Tenant #{$tenant->id}", $text);
        $this->assertStringContainsString('Detalle Bot', $text);
        $this->assertStringContainsString('✅ Activo', $text);
    }

    public function test_tenant_command_shows_a_friendly_error_when_the_id_is_missing(): void
    {
        $this->runCommand(TenantCommand::class, '/tenant');

        $this->assertStringContainsString('Uso correcto', $this->lastReplyText());
    }

    public function test_tenant_command_shows_a_friendly_error_for_a_non_existent_tenant(): void
    {
        $this->runCommand(TenantCommand::class, '/tenant 424242');

        $this->assertStringContainsString('No existe un tenant con id', $this->lastReplyText());
    }

    public function test_query_commands_reject_an_unauthorized_user(): void
    {
        $this->runCommand(MetricasCommand::class, '/metricas', 555);

        $this->assertStringContainsString('No estás autorizado', $this->lastReplyText());
    }
}
