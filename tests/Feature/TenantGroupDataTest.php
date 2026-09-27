<?php

namespace Tests\Feature;

use App\Filament\Tenant\Pages\TenantPage;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Datos del GRUPO editables inline desde "Mi Grupo" (revisión de Frankie, 2026-09-27).
 *
 * Reglas:
 *  - los edita **sólo el owner** (misma regla que la gestión de usuarios de esa página),
 *  - se edita el **nombre**; el **email del grupo NO** (y acá está el motivo, ver abajo).
 *
 * ⚠️ Hallazgo de este trabajo: `isTenantOwner()` se resuelve comparando `users.email` con
 * `tenants.email`, y no hay otra señal — la columna `tenants.owner_id` se eliminó en la migración
 * 2025_04_26 y nunca se reemplazó. O sea: **cambiar el email del grupo le sacaría el panel al owner**
 * (no podría gestionar usuarios ni volver a editarlo). Por eso el email es de sólo lectura en la UI y
 * el guardado lo rechaza con un mensaje, en vez de romper permisos en silencio.
 */
class TenantGroupDataTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{tenant: Tenant, owner: User, miembro: User} */
    private function entorno(): array
    {
        $tenant = Tenant::factory()->create([
            'active' => true,
            'name' => 'Grupo Viejo',
            'email' => 'grupo@ejemplo.test',
        ]);

        // El owner se define por email (es la única señal que hay hoy).
        $owner = User::factory()->create([
            'tenant_id' => $tenant->id,
            'email' => 'grupo@ejemplo.test',
        ]);

        $miembro = User::factory()->create([
            'tenant_id' => $tenant->id,
            'email' => 'miembro@ejemplo.test',
        ]);

        return compact('tenant', 'owner', 'miembro');
    }

    public function test_el_owner_puede_editar_el_nombre_del_grupo(): void
    {
        ['tenant' => $tenant, 'owner' => $owner] = $this->entorno();
        $this->actingAs($owner);

        Livewire::test(TenantPage::class)
            ->call('editTenant')
            ->set('tenantName', 'Grupo Nuevo')
            ->call('updateTenant')
            ->assertHasNoErrors();

        $this->assertSame('Grupo Nuevo', $tenant->fresh()->name);
    }

    public function test_un_miembro_que_no_es_owner_no_puede_editar_el_grupo(): void
    {
        ['tenant' => $tenant, 'miembro' => $miembro] = $this->entorno();
        $this->actingAs($miembro);

        Livewire::test(TenantPage::class)
            ->call('editTenant')
            ->set('tenantName', 'Lo cambio yo')
            ->call('updateTenant');

        $this->assertSame('Grupo Viejo', $tenant->fresh()->name, 'El nombre del grupo no lo cambia cualquier miembro.');
    }

    public function test_el_email_del_grupo_se_puede_cambiar(): void
    {
        // Dejó de estar bloqueado: con el ownership por id, el email del grupo ya no arrastra permisos.
        ['tenant' => $tenant, 'owner' => $owner] = $this->entorno();
        $tenant->update(['owner_user_id' => $owner->id]);
        $this->actingAs($owner);

        Livewire::test(TenantPage::class)
            ->call('editTenant')
            ->set('tenantEmail', 'nuevo-grupo@ejemplo.test')
            ->call('updateTenant')
            ->assertHasNoErrors();

        $this->assertSame('nuevo-grupo@ejemplo.test', $tenant->fresh()->email);
        $this->assertTrue($owner->fresh()->isTenantOwner(), 'El admin no puede perder el panel por esto.');
    }

    public function test_el_owner_sigue_siendo_owner_despues_de_editar_el_grupo(): void
    {
        ['tenant' => $tenant, 'owner' => $owner] = $this->entorno();
        $this->actingAs($owner);

        Livewire::test(TenantPage::class)
            ->call('editTenant')
            ->set('tenantName', 'Otro Nombre')
            ->set('tenantEmail', 'grupo@ejemplo.test')
            ->call('updateTenant')
            ->assertHasNoErrors();

        $this->assertTrue($owner->fresh()->isTenantOwner());
        $this->assertSame('Otro Nombre', $tenant->fresh()->name);
    }
}
