<?php

namespace Tests\Feature;

use App\Filament\Tenant\Pages\TenantPage;
use App\Models\SecurityEvent;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\TeamUserInvitationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Cambiar el email del grupo: qué pasa con la dirección nueva (revisión del dueño del repo, 2026-09-27).
 *
 * El email del grupo es su **canal de contacto**, no una credencial (los permisos van por
 * `tenants.owner_user_id`). Entonces:
 *
 *  - si la dirección no tiene usuario, **no se crea ninguno solo**: se ofrece explícitamente crearlo y
 *    mandarle el link para definir su contraseña (un cambio de contacto no debería dar de alta una
 *    cuenta: un typo generaría un usuario fantasma con acceso);
 *  - si la dirección pertenece a **otro** grupo (o a una cuenta sin grupo), se rechaza con un mensaje:
 *    no daría permisos, pero dejaría un dato cruzado entre grupos.
 */
class TenantEmailChangeTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{tenant: Tenant, owner: User} */
    private function entorno(): array
    {
        $tenant = Tenant::factory()->create([
            'active' => true,
            'name' => 'Grupo Test',
            'email' => 'grupo@ejemplo.test',
        ]);

        $owner = User::factory()->create([
            'tenant_id' => $tenant->id,
            'email' => 'grupo@ejemplo.test',
        ]);

        $tenant->update(['owner_user_id' => $owner->id]);

        return compact('tenant', 'owner');
    }

    public function test_el_casillero_solo_se_ofrece_si_el_email_cambio_y_no_tiene_usuario(): void
    {
        // Revisión del dueño del repo, 2026-09-27: "debe verse solo si se modificó el correo del tenant admin,
        // sino es innecesario".
        Notification::fake();
        ['tenant' => $tenant, 'owner' => $owner] = $this->entorno();
        $this->actingAs($owner);

        $componente = Livewire::test(TenantPage::class)->call('editTenant');

        // Mismo email que el grupo: nada que crear.
        $this->assertFalse($componente->instance()->puedeCrearPersonaParaElEmail());

        // Cambió y no tiene usuario: se ofrece.
        $componente->set('tenantEmail', 'nueva@ejemplo.test');
        $this->assertTrue($componente->instance()->puedeCrearPersonaParaElEmail());

        // Cambió a una dirección que YA es de alguien del grupo: no se ofrece (no se duplica).
        User::factory()->create(['tenant_id' => $tenant->id, 'email' => 'ocupada@ejemplo.test']);
        $componente->set('tenantEmail', 'ocupada@ejemplo.test');
        $this->assertFalse($componente->instance()->puedeCrearPersonaParaElEmail());
    }

    public function test_cambiar_el_email_no_crea_usuarios_solo(): void
    {
        Notification::fake();
        ['tenant' => $tenant, 'owner' => $owner] = $this->entorno();
        $this->actingAs($owner);

        Livewire::test(TenantPage::class)
            ->call('editTenant')
            ->set('tenantEmail', 'contacto@ejemplo.test')
            ->call('updateTenant')
            ->assertHasNoErrors();

        $this->assertSame('contacto@ejemplo.test', $tenant->fresh()->email);
        $this->assertNull(
            User::where('email', 'contacto@ejemplo.test')->first(),
            'Un cambio de contacto no debe dar de alta una cuenta.'
        );
        Notification::assertNothingSent();
    }

    public function test_con_el_casillero_marcado_se_crea_la_persona_y_se_le_manda_el_link(): void
    {
        Notification::fake();
        ['tenant' => $tenant, 'owner' => $owner] = $this->entorno();
        $this->actingAs($owner);

        Livewire::test(TenantPage::class)
            ->call('editTenant')
            ->set('tenantEmail', 'nueva.persona@ejemplo.test')
            ->set('createUserForEmail', true)
            ->call('updateTenant')
            ->assertHasNoErrors();

        $nueva = User::where('email', 'nueva.persona@ejemplo.test')->first();

        $this->assertNotNull($nueva, 'Con el casillero marcado se crea la persona.');
        $this->assertSame($tenant->id, $nueva->tenant_id);
        $this->assertTrue((bool) $nueva->force_password_change);

        Notification::assertSentTo($nueva, TeamUserInvitationNotification::class);
        $this->assertSame(
            1,
            SecurityEvent::where('event', SecurityEvent::TEAM_USER_INVITED)->count(),
            'El alta tiene que quedar en la trazabilidad.'
        );
    }

    public function test_si_la_direccion_ya_tiene_usuario_en_el_grupo_no_se_duplica(): void
    {
        Notification::fake();
        ['tenant' => $tenant, 'owner' => $owner] = $this->entorno();
        $miembro = User::factory()->create([
            'tenant_id' => $tenant->id,
            'email' => 'miembro@ejemplo.test',
        ]);
        $this->actingAs($owner);

        Livewire::test(TenantPage::class)
            ->call('editTenant')
            ->set('tenantEmail', 'miembro@ejemplo.test')
            ->set('createUserForEmail', true)
            ->call('updateTenant')
            ->assertHasNoErrors();

        $this->assertSame('miembro@ejemplo.test', $tenant->fresh()->email);
        $this->assertSame(1, User::where('email', 'miembro@ejemplo.test')->count());
        $this->assertSame($miembro->id, User::where('email', 'miembro@ejemplo.test')->value('id'));
    }

    public function test_no_se_puede_usar_la_direccion_de_otro_grupo(): void
    {
        Notification::fake();
        ['tenant' => $tenant, 'owner' => $owner] = $this->entorno();
        $this->actingAs($owner);

        // Otro grupo, con su propia persona.
        $otroTenant = Tenant::factory()->create(['active' => true, 'email' => 'otro@ejemplo.test']);
        User::factory()->create(['tenant_id' => $otroTenant->id, 'email' => 'ajeno@ejemplo.test']);

        Livewire::test(TenantPage::class)
            ->call('editTenant')
            ->set('tenantEmail', 'ajeno@ejemplo.test')
            ->call('updateTenant')
            ->assertHasErrors('tenantEmail');

        $this->assertSame('grupo@ejemplo.test', $tenant->fresh()->email, 'No se tocó nada.');
    }

    public function test_tampoco_se_puede_usar_una_cuenta_sin_grupo(): void
    {
        // Caso superadmin (tenant_id null): mismo criterio, no se cruza.
        Notification::fake();
        ['tenant' => $tenant, 'owner' => $owner] = $this->entorno();
        $this->actingAs($owner);

        User::factory()->create(['tenant_id' => null, 'email' => 'admin@plataforma.test']);

        Livewire::test(TenantPage::class)
            ->call('editTenant')
            ->set('tenantEmail', 'admin@plataforma.test')
            ->call('updateTenant')
            ->assertHasErrors('tenantEmail');

        $this->assertSame('grupo@ejemplo.test', $tenant->fresh()->email);
    }
}
