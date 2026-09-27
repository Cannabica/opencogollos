<?php

namespace Tests\Feature;

use App\Filament\Tenant\Pages\Cuenta;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * T10.1 — "Mi cuenta": perfil del usuario en el panel del cultivador.
 *
 * Antes no había ningún lugar donde editar el nombre ni el email: el usuario quedaba con lo que le
 * puso el seeder o el owner al invitarlo.
 *
 * La contraseña NO vive acá (tiene su página, `CambiarPassword`, y su propia trazabilidad de
 * seguridad): el último test de este archivo es la garantía de que esta pantalla no la toca.
 */
class CuentaPageTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(array $extra = []): User
    {
        $tenant = Tenant::factory()->create(['active' => true]);

        return User::factory()->create(array_merge([
            'tenant_id' => $tenant->id,
            'name' => 'Nombre Viejo',
            'password' => Hash::make('Password1!'),
        ], $extra));
    }

    public function test_el_owner_no_puede_cambiarse_el_email_desde_mis_datos(): void
    {
        // Para el owner, su email ES el del grupo y es lo que lo identifica como owner: si lo cambiara
        // desde acá, perdería el panel (medido 2026-09-27). El campo viene deshabilitado y el guardado
        // también lo rechaza.
        \Illuminate\Support\Facades\Notification::fake();

        $tenant = Tenant::factory()->create(['active' => true, 'email' => 'owner@ejemplo.test']);
        $owner = User::factory()->create([
            'tenant_id' => $tenant->id,
            'email' => 'owner@ejemplo.test',
            'password' => \Illuminate\Support\Facades\Hash::make('Password1!'),
        ]);
        $this->actingAs($owner);

        Livewire::test(Cuenta::class)
            ->fillForm([
                'name' => $owner->name,
                'email' => 'otro@ejemplo.test',
            ])
            ->call('guardar');

        // El campo viene deshabilitado, así que el intento ni llega: la garantía es que el email NO
        // cambia y el owner NO pierde el panel (no hace falta que haya un mensaje de error).
        $this->assertSame('owner@ejemplo.test', $owner->fresh()->email);
        $this->assertTrue($owner->fresh()->isTenantOwner(), 'No puede quedar sin panel.');
        \Illuminate\Support\Facades\Notification::assertNothingSent();
    }

    public function test_el_miembro_si_puede_pedir_el_cambio_de_su_email(): void
    {
        // El bloqueo es sólo para el owner: un miembro tiene su propio email y puede cambiarlo (con
        // doble opt-in, ver EmailChangeRequestTest).
        \Illuminate\Support\Facades\Notification::fake();

        $tenant = Tenant::factory()->create(['active' => true, 'email' => 'grupo@ejemplo.test']);
        $miembro = User::factory()->create([
            'tenant_id' => $tenant->id,
            'email' => 'miembro@ejemplo.test',
            'password' => \Illuminate\Support\Facades\Hash::make('Password1!'),
        ]);
        $this->actingAs($miembro);

        Livewire::test(Cuenta::class)
            ->fillForm([
                'name' => $miembro->name,
                'email' => 'nuevo@ejemplo.test',
                'current_password' => 'Password1!',
            ])
            ->call('guardar')
            ->assertHasNoFormErrors();

        $this->assertSame(
            1,
            \App\Models\EmailChangeRequest::where('user_id', $miembro->id)->count(),
            'El miembro tiene que poder pedir el cambio (queda pendiente de confirmación).'
        );
    }

    public function test_el_usuario_puede_cambiar_su_nombre(): void
    {
        \Illuminate\Support\Facades\Notification::fake();
        $user = $this->usuario();
        $this->actingAs($user);

        Livewire::test(Cuenta::class)
            ->fillForm([
                'name' => 'Nombre Nuevo',
                'email' => $user->email,
            ])
            ->call('guardar')
            ->assertHasNoFormErrors();

        $this->assertSame('Nombre Nuevo', $user->fresh()->name);
        $this->assertSame(
            $user->email,
            $user->fresh()->email,
            'El email no se cambia desde acá: es la credencial de login y va por doble opt-in (EmailChangeRequestTest).'
        );
    }

    public function test_el_email_no_puede_repetir_el_de_otro_usuario(): void
    {
        $user = $this->usuario();
        $this->usuario(['email' => 'ocupado@ejemplo.test']);
        $this->actingAs($user);

        Livewire::test(Cuenta::class)
            ->fillForm([
                'name' => $user->name,
                'email' => 'ocupado@ejemplo.test',
            ])
            ->call('guardar')
            ->assertHasFormErrors(['email']);

        $this->assertSame($user->email, $user->fresh()->email, 'El email no debe haber cambiado.');
    }

    public function test_el_nombre_es_obligatorio(): void
    {
        $user = $this->usuario();
        $this->actingAs($user);

        Livewire::test(Cuenta::class)
            ->fillForm([
                'name' => '',
                'email' => $user->email,
            ])
            ->call('guardar')
            ->assertHasFormErrors(['name']);
    }

    public function test_esta_pantalla_no_toca_la_contrasena(): void
    {
        $user = $this->usuario();
        $passwordOriginal = $user->password;
        $this->actingAs($user);

        Livewire::test(Cuenta::class)
            ->fillForm([
                'name' => 'Otro Nombre',
                'email' => $user->email,
            ])
            ->call('guardar')
            ->assertHasNoFormErrors();

        $this->assertSame(
            $passwordOriginal,
            $user->fresh()->password,
            'El perfil no debe tocar la contraseña: para eso está "Cambiar contraseña".'
        );
    }
}
