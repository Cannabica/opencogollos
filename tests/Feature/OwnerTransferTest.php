<?php

namespace Tests\Feature;

use App\Filament\Tenant\Pages\TenantPage;
use App\Models\SecurityEvent;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Transferir la administración del grupo (`tenants.owner_user_id`) desde la UI.
 *
 * Cierra el ownership con señal propia: hasta acá no había forma de designar administrador, así que un
 * grupo cuyo email no coincidía con ningún usuario quedaba sin salida desde la app, y una persona nueva
 * no podía llegar a administrar nunca (el dueño del repo lo chocó el 2026-09-27: estaba logueado con una persona
 * recién invitada y no podía ver el formulario del grupo).
 */
class OwnerTransferTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{tenant: Tenant, admin: User, miembro: User} */
    private function entorno(): array
    {
        $tenant = Tenant::factory()->create(['active' => true, 'email' => 'grupo@ejemplo.test']);

        $admin = User::factory()->create([
            'tenant_id' => $tenant->id,
            'email' => 'grupo@ejemplo.test',
        ]);

        $miembro = User::factory()->create([
            'tenant_id' => $tenant->id,
            'email' => 'miembro@ejemplo.test',
        ]);

        $tenant->update(['owner_user_id' => $admin->id]);

        return compact('tenant', 'admin', 'miembro');
    }

    public function test_el_admin_puede_designar_a_otro_miembro(): void
    {
        Notification::fake();
        ['tenant' => $tenant, 'admin' => $admin, 'miembro' => $miembro] = $this->entorno();
        $this->actingAs($admin);

        Livewire::test(TenantPage::class)->call('makeOwner', $miembro->id);

        $this->assertSame($miembro->id, (int) $tenant->fresh()->owner_user_id);
        $this->assertTrue($miembro->fresh()->isTenantOwner(), 'El designado administra.');
        $this->assertFalse($admin->fresh()->isTenantOwner(), 'El anterior deja de administrar.');

        $evento = SecurityEvent::where('event', SecurityEvent::OWNER_CHANGED)->first();
        $this->assertNotNull($evento, 'La transferencia tiene que quedar registrada.');
        $this->assertSame('transferred', $evento->context);
    }

    public function test_un_miembro_no_puede_designar_administrador(): void
    {
        Notification::fake();
        ['tenant' => $tenant, 'admin' => $admin, 'miembro' => $miembro] = $this->entorno();
        $this->actingAs($miembro);

        Livewire::test(TenantPage::class)->call('makeOwner', $miembro->id);

        $this->assertSame($admin->id, (int) $tenant->fresh()->owner_user_id, 'No cambió nada.');
        $this->assertSame(0, SecurityEvent::where('event', SecurityEvent::OWNER_CHANGED)->count());
    }

    public function test_no_se_puede_designar_a_alguien_de_otro_grupo(): void
    {
        Notification::fake();
        ['tenant' => $tenant, 'admin' => $admin] = $this->entorno();
        $this->actingAs($admin);

        $ajeno = User::factory()->create([
            'tenant_id' => Tenant::factory()->create(['active' => true])->id,
            'email' => 'ajeno@ejemplo.test',
        ]);

        Livewire::test(TenantPage::class)->call('makeOwner', $ajeno->id);

        $this->assertSame($admin->id, (int) $tenant->fresh()->owner_user_id);
    }

    public function test_despues_de_transferir_el_anterior_ya_no_ve_las_acciones_de_admin(): void
    {
        // La UI se recalcula al transferir: el que transfirió deja de administrar en el acto.
        Notification::fake();
        ['admin' => $admin, 'miembro' => $miembro] = $this->entorno();
        $this->actingAs($admin);

        Livewire::test(TenantPage::class)
            ->call('makeOwner', $miembro->id)
            ->assertSet('isOwner', false);
    }
}
