<?php

namespace Tests\Feature\Telegram;

use App\Models\Tenant;
use App\Models\User;
use App\Telegram\Admin\Commands\ActivarCommand;
use App\Telegram\Admin\Commands\CancelarCommand;
use App\Telegram\Admin\Commands\ConfirmarCommand;
use App\Telegram\Admin\Commands\DesactivarCommand;
use App\Telegram\Admin\Commands\PendientesCommand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Telegram\Bot\Api;
use Telegram\Bot\Commands\Command;
use Telegram\Bot\Objects\Message;
use Telegram\Bot\Objects\Update;
use Tests\TestCase;

class AdminActionCommandsTest extends TestCase
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
            'update_id' => 300,
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

    public function test_pendientes_lists_only_inactive_tenants(): void
    {
        Tenant::factory()->create(['name' => 'Activo SA']);
        Tenant::factory()->inactive()->create(['name' => 'Esperando']);

        $this->runCommand(PendientesCommand::class, '/pendientes');

        $text = $this->lastReplyText();
        $this->assertStringContainsString('pendientes de activación', $text);
        $this->assertStringContainsString('Esperando', $text);
        $this->assertStringNotContainsString('Activo SA', $text);
    }

    public function test_pendientes_reports_none_when_everything_is_active(): void
    {
        Tenant::factory()->create();

        $this->runCommand(PendientesCommand::class, '/pendientes');

        $this->assertStringContainsString('No hay tenants pendientes', $this->lastReplyText());
    }

    public function test_activar_asks_for_confirmation_and_confirmar_executes(): void
    {
        $tenant = Tenant::factory()->inactive()->create(['name' => 'A Activar']);

        $this->runCommand(ActivarCommand::class, "/activar {$tenant->id}");
        $this->assertStringContainsString('¿Confirmás <b>activar</b>', $this->lastReplyText());
        $this->assertFalse((bool) Tenant::find($tenant->id)->active);

        $this->runCommand(ConfirmarCommand::class, '/confirmar');

        $this->assertStringContainsString('fue activado correctamente', $this->lastReplyText());
        $this->assertTrue((bool) Tenant::find($tenant->id)->active);
    }

    public function test_desactivar_asks_for_confirmation_and_confirmar_executes(): void
    {
        $tenant = Tenant::factory()->create(['name' => 'A Desactivar']);

        $this->runCommand(DesactivarCommand::class, "/desactivar {$tenant->id}");
        $this->assertStringContainsString('¿Confirmás <b>desactivar</b>', $this->lastReplyText());

        $this->runCommand(ConfirmarCommand::class, '/confirmar');

        $this->assertStringContainsString('fue desactivado correctamente', $this->lastReplyText());
        $this->assertFalse((bool) Tenant::find($tenant->id)->active);
    }

    public function test_confirmar_without_pending_action_is_rejected(): void
    {
        $this->runCommand(ConfirmarCommand::class, '/confirmar');

        $this->assertStringContainsString('No hay ninguna acción pendiente', $this->lastReplyText());
    }

    public function test_cancelar_discards_the_pending_action(): void
    {
        $tenant = Tenant::factory()->inactive()->create(['name' => 'No Activar']);

        $this->runCommand(ActivarCommand::class, "/activar {$tenant->id}");
        $this->runCommand(CancelarCommand::class, '/cancelar');
        $this->runCommand(ConfirmarCommand::class, '/confirmar');

        $this->assertStringContainsString('No hay ninguna acción pendiente', $this->lastReplyText());
        $this->assertFalse((bool) Tenant::find($tenant->id)->active);
    }

    public function test_activar_an_already_active_tenant_does_nothing(): void
    {
        $tenant = Tenant::factory()->create(['name' => 'Ya Activo']);

        $this->runCommand(ActivarCommand::class, "/activar {$tenant->id}");

        $this->assertStringContainsString('ya está activo', $this->lastReplyText());
        $this->assertTrue((bool) Tenant::find($tenant->id)->active);
    }

    public function test_activar_a_missing_tenant_is_rejected(): void
    {
        $this->runCommand(ActivarCommand::class, '/activar 424242');

        $this->assertStringContainsString('No existe un tenant', $this->lastReplyText());
    }

    public function test_action_commands_reject_an_unauthorized_user(): void
    {
        Tenant::factory()->inactive()->create();

        $this->runCommand(ActivarCommand::class, '/activar 1', 555);

        $this->assertStringContainsString('No estás autorizado', $this->lastReplyText());
    }

    public function test_deactivation_notifies_the_tenant_user_by_email(): void
    {
        $tenant = Tenant::factory()->create(['name' => 'Con Usuario']);
        User::factory()->create(['tenant_id' => $tenant->id, 'email' => 'usuario@example.com']);

        $this->runCommand(DesactivarCommand::class, "/desactivar {$tenant->id}");
        $this->runCommand(ConfirmarCommand::class, '/confirmar');

        $this->assertStringContainsString('fue desactivado correctamente', $this->lastReplyText());
    }
}
