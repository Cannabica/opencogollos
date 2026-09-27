<?php

namespace Tests\Feature;

use App\Filament\Tenant\Pages\PasswordChange;
use App\Models\SecurityEvent;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * El cambio obligatorio del primer acceso tiene que **terminar** (no ser un loop).
 *
 * Frankie, 2026-09-27: "estoy en un loop que siempre me saca la sesión y me vuelve a pedir que haga el
 * cambio de clave obligatorio". El flujo vive en tres piezas que tienen que estar de acuerdo:
 *
 *   1. `PasswordChange::changePassword()` guarda y pone `force_password_change = false`,
 *   2. el middleware `CheckPasswordChange` redirige al cambio **sólo** mientras ese flag esté en true,
 *   3. el redirect post-cambio tiene que ser efectivo (`return`, no una llamada suelta).
 *
 * Este test recorre el ciclo completo: cambio -> flag persistido -> entrar al panel NO vuelve a pedir
 * el cambio.
 */
class PasswordChangeLoopTest extends TestCase
{
    use RefreshDatabase;

    private function usuarioConCambioPendiente(): User
    {
        $tenant = Tenant::factory()->create(['active' => true]);

        return User::factory()->create([
            'tenant_id' => $tenant->id,
            'email' => 'primer.ingreso@ejemplo.test',
            'password' => Hash::make('Temporal1!'),
            'force_password_change' => true,
        ]);
    }

    private function cambiarLaContrasena(User $user): void
    {
        $this->actingAs($user);

        Livewire::test(PasswordChange::class)
            ->fillForm([
                'current_password' => 'Temporal1!',
                'new_password' => 'NuevaClave2@',
                'new_password_confirmation' => 'NuevaClave2@',
            ])
            ->call('changePassword');
    }

    public function test_el_cambio_obligatorio_persiste_el_flag(): void
    {
        Notification::fake();
        $user = $this->usuarioConCambioPendiente();

        $this->cambiarLaContrasena($user);

        $this->assertFalse(
            (bool) $user->fresh()->force_password_change,
            'Si el flag no se persiste, el middleware vuelve a pedir el cambio: loop.'
        );
        $this->assertTrue(Hash::check('NuevaClave2@', $user->fresh()->password));
    }

    public function test_despues_del_cambio_el_panel_no_vuelve_a_pedirlo(): void
    {
        // Este es el test del loop: entra al panel con el usuario ya cambiado. Si el flag quedó en
        // true, `CheckPasswordChange` responde 302 (redirige al cambio) y esto falla.
        Notification::fake();
        $user = $this->usuarioConCambioPendiente();

        $this->cambiarLaContrasena($user);

        $this->actingAs($user->fresh())
            ->get('/tenant/tenant-page')
            ->assertOk();
    }

    public function test_mientras_el_cambio_esta_pendiente_el_panel_lo_manda_a_cambiarla(): void
    {
        // El otro lado de la regla: con el flag en true, el panel NO deja pasar.
        Notification::fake();
        $user = $this->usuarioConCambioPendiente();

        $this->actingAs($user)
            ->get('/tenant/tenant-page')
            ->assertRedirect(route('filament.tenant.pages.password-change'));
    }

    public function test_el_cambio_deja_su_evento_de_seguridad(): void
    {
        Notification::fake();
        $user = $this->usuarioConCambioPendiente();

        $this->cambiarLaContrasena($user);

        $evento = SecurityEvent::where('user_id', $user->id)->first();
        $this->assertNotNull($evento);
        $this->assertSame(SecurityEvent::PASSWORD_CHANGED, $evento->event);
        $this->assertSame(SecurityEvent::CONTEXT_FORCED, $evento->context);
    }
}
