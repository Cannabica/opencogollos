<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckPasswordChange;
use App\Http\Middleware\CheckTenantActivation;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Mechanisms\PersistentMiddleware\PersistentMiddleware;
use Tests\TestCase;

/**
 * Los requests de Livewire (POST /livewire/update) NO pasan por el stack del panel:
 * esa ruta se registra sólo con el grupo `web`. Los middlewares de app del panel
 * tenant tienen que estar marcados como persistentes (Livewire los re-aplica
 * matcheando la ruta original del snapshot) o el control de acceso se saltea:
 * el GET redirige a la pantalla de activación pero los widgets y las acciones se
 * hidratan igual.
 *
 * Medido antes del fix (2026-09-28, snapshot de la base de producción): un usuario
 * de un tenant con active=false obtenía el widget de gráficos por /livewire/update.
 */
class TenantActivationOnLivewireTest extends TestCase
{
    use RefreshDatabase;

    private function tenantYUsuario(bool $activo = true): array
    {
        $tenant = Tenant::withoutGlobalScopes()->create([
            'name' => 'Grupo',
            'email' => 'grupo@ejemplo.test',
            'active' => $activo,
        ]);

        $user = User::withoutGlobalScopes()->create([
            'name' => 'Cultivador',
            'email' => 'cultivador@ejemplo.test',
            'password' => bcrypt('password'),
            'tenant_id' => $tenant->id,
            'email_verified_at' => now(),
        ]);

        return [$tenant, $user];
    }

    /** Payload de Livewire con un snapshot REAL que devolvió el panel. */
    private function payloadDeLivewire(string $html): array
    {
        preg_match('/name="csrf-token"\s+content="([^"]+)"/', $html, $token);
        preg_match('/wire:snapshot="([^"]+)"/', $html, $snapshot);

        return [
            'csrf' => $token[1] ?? '',
            'body' => [
                '_token' => $token[1] ?? '',
                'components' => [[
                    'snapshot' => html_entity_decode($snapshot[1] ?? ''),
                    'updates' => [],
                    'calls' => [],
                ]],
            ],
        ];
    }

    private function postLivewire(string $html)
    {
        $payload = $this->payloadDeLivewire($html);

        // postJson (no post): HandleRequests aborta 404 si el payload no llega como
        // JSON, porque en form-data un array vacío no se puede representar.
        return $this->postJson('/livewire/update', $payload['body'], [
            'X-Livewire' => 'true',
            'X-CSRF-TOKEN' => $payload['csrf'],
        ]);
    }

    public function test_los_middlewares_del_panel_tenant_son_persistentes_en_livewire(): void
    {
        $persistentes = app(PersistentMiddleware::class)->getPersistentMiddleware();

        foreach ([CheckTenantActivation::class, CheckPasswordChange::class] as $middleware) {
            $this->assertContains(
                $middleware,
                $persistentes,
                "{$middleware} tiene que estar en la lista de persistentes: si se saca, el panel se sigue operando por Livewire aunque el tenant esté desactivado."
            );
        }
    }

    public function test_un_tenant_activo_puede_operar_por_livewire_control(): void
    {
        [, $user] = $this->tenantYUsuario(activo: true);
        $this->actingAs($user);

        $html = $this->get('/tenant/analitycs')->assertSuccessful()->getContent();

        // Control del test: con el tenant activo el mismo request pasa (si esto
        // fallara por CSRF/checksum, el test de abajo pasaría por el motivo equivocado).
        $this->assertSame(200, $this->postLivewire($html)->getStatusCode());
    }

    public function test_un_tenant_desactivado_no_puede_operar_el_panel_por_livewire(): void
    {
        [$tenant, $user] = $this->tenantYUsuario(activo: true);
        $this->actingAs($user);

        // El usuario ya tenía la página abierta (su snapshot es válido) y recién
        // después le desactivan el grupo.
        $html = $this->get('/tenant/analitycs')->assertSuccessful()->getContent();

        Tenant::withoutGlobalScopes()->where('id', $tenant->id)->update(['active' => false]);

        // Instancia fresca del usuario: en producción cada request vuelve a leer
        // `$user->tenant` de la base. Si se reusa el modelo cargado en memoria, la
        // relación `tenant` queda cacheada con active=true y el test mide humo.
        $this->actingAs(User::withoutGlobalScopes()->findOrFail($user->id));

        $response = $this->postLivewire($html);

        $this->assertNotSame(
            200,
            $response->getStatusCode(),
            'Un tenant desactivado pudo operar el panel por /livewire/update.'
        );
        $this->assertSame(
            url('/tenant/activation-pending'),
            $response->headers->get('Location'),
            'El request de Livewire de un tenant desactivado tiene que caer en la pantalla de activación.'
        );
    }
}
