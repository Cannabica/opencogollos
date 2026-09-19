<?php

namespace Tests\Feature;

use App\Http\Controllers\PostponeProductReminderController;
use App\Jobs\SendDelayedProductNotification;
use App\Models\Tenant;
use App\Models\User;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * Acción "Posponer" del recordatorio de aplicación de producto (T2.8, 2026-09-19).
 *
 * Contexto: la acción original usaba ->action(closure) dentro de una notificación de base de
 * datos, y Filament NO serializa closures (Action::toArray() → Action::fromArray()), así que el
 * botón era mudo. Ahora es una URL firmada que reencola el job. Estos tests cubren las dos
 * mitades: que la URL haga lo que promete y que no se pueda usar una URL que no sea la propia.
 */
class PostponeProductReminderTest extends TestCase
{
    use RefreshDatabase;

    private function signedUrlFor(User $user, int $actionId = 7, string $type = 'flora', int $count = 3): string
    {
        return URL::signedRoute('actions.postpone-notification', [
            'type' => $type,
            'count' => $count,
            'action' => $actionId,
            'user' => $user->getKey(),
        ]);
    }

    /**
     * El corazón del bug: la acción tiene que SOBREVIVIR el viaje a la columna `data` y volver
     * con su URL (una closure no sobrevive; esto es lo que dejaba el botón mudo sin error).
     */
    public function test_the_postpone_action_survives_the_database_round_trip(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        (new SendDelayedProductNotification('flora', 3, $tenant->id, 42))->handle();

        $guardada = $user->notifications()->first();
        $this->assertNotNull($guardada, 'la notificación no se guardó en la base');

        $posponer = collect(FilamentNotification::fromDatabase($guardada)->getActions())
            ->first(fn ($accion) => $accion->getName() === 'postpone');

        $this->assertNotNull($posponer, 'no se rehidrató la acción Posponer');
        $this->assertNotNull($posponer->getUrl(), 'la acción Posponer quedó SIN url => botón mudo');
        $this->assertStringContainsString('/recordatorios/posponer/flora/3/42', $posponer->getUrl());
        $this->assertStringContainsString('signature=', $posponer->getUrl());
        $this->assertTrue($posponer->shouldMarkAsRead(), 'la acción no marca leída la notificación');
    }

    public function test_the_signed_url_requeues_the_reminder_24_hours_later(): void
    {
        config(['queue.default' => 'database']);

        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($user)
            ->get($this->signedUrlFor($user, actionId: 42))
            ->assertRedirect('/tenant');

        $job = DB::table('jobs')->first();

        $this->assertNotNull($job, 'el recordatorio no se encoló');

        $payload = json_decode($job->payload, true);
        $this->assertSame(SendDelayedProductNotification::class, $payload['displayName']);

        // El delay real, medido en la tabla: ~24 h (margen de 60 s por el tiempo de ejecución).
        $esperado = now()->addHours(PostponeProductReminderController::POSTPONE_HOURS)->timestamp;
        $this->assertGreaterThanOrEqual($esperado - 60, (int) $job->available_at);
        $this->assertLessThanOrEqual($esperado + 60, (int) $job->available_at);
    }

    public function test_the_requeued_job_keeps_the_original_parameters(): void
    {
        config(['queue.default' => 'database']);

        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($user)->get($this->signedUrlFor($user, actionId: 99, type: 'plague', count: 5));

        $payload = json_decode(DB::table('jobs')->first()->payload, true);
        $comando = unserialize($payload['data']['command']);

        $this->assertSame('plague', $comando->applicationType);
        $this->assertSame(5, $comando->plantsCount);
        $this->assertSame((int) $tenant->id, $comando->tenantId);
        $this->assertSame(99, $comando->actionId);
    }

    public function test_a_forged_url_is_rejected(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($user)
            ->get('/recordatorios/posponer/flora/3/7?user=' . $user->getKey())
            ->assertForbidden();
    }

    public function test_a_link_signed_for_another_user_is_rejected(): void
    {
        $tenant = Tenant::factory()->create();
        $otro = User::factory()->create(['tenant_id' => $tenant->id]);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($user)
            ->get($this->signedUrlFor($otro))
            ->assertForbidden();
    }

    public function test_a_guest_cannot_use_the_link(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        $this->get($this->signedUrlFor($user))->assertRedirect('/tenant/login');
    }
}
