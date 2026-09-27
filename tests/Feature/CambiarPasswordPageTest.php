<?php

namespace Tests\Feature;

use App\Filament\Tenant\Pages\CambiarPassword;
use App\Filament\Tenant\Pages\PasswordChange;
use App\Models\SecurityEvent;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * T10.4 — "Cambiar contraseña" (página propia) + su trazabilidad de seguridad.
 *
 * Dos reglas del producto (corrección de diseño de Frankie, 2026-09-27):
 *  1. El cambio de contraseña vive en su propia página, no dentro de "Mi cuenta".
 *  2. Cada cambio queda registrado en `security_events` — una tabla PROPIA, separada de
 *     `usage_events` (que es telemetría de uso y mide pageviews, no acciones de cuenta).
 *
 * El `context` es lo que hace útil el registro para auditar: no es lo mismo que el usuario haya
 * elegido cambiarla (`voluntary`, esta página) a que el sistema lo haya obligado (`forced`, la
 * página del primer acceso `PasswordChange`).
 */
class CambiarPasswordPageTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(array $extra = []): User
    {
        $tenant = Tenant::factory()->create(['active' => true]);

        return User::factory()->create(array_merge([
            'tenant_id' => $tenant->id,
            'password' => Hash::make('Password1!'),
        ], $extra));
    }

    public function test_el_usuario_puede_cambiar_su_contrasena(): void
    {
        $user = $this->usuario();
        $this->actingAs($user);

        Livewire::test(CambiarPassword::class)
            ->fillForm([
                'current_password' => 'Password1!',
                'new_password' => 'NuevaClave2@',
                'new_password_confirmation' => 'NuevaClave2@',
            ])
            ->call('guardar')
            ->assertHasNoFormErrors();

        $this->assertTrue(Hash::check('NuevaClave2@', $user->fresh()->password));
    }

    public function test_el_cambio_voluntario_queda_registrado_en_seguridad(): void
    {
        $user = $this->usuario();
        $this->actingAs($user);

        Livewire::test(CambiarPassword::class)
            ->fillForm([
                'current_password' => 'Password1!',
                'new_password' => 'NuevaClave2@',
                'new_password_confirmation' => 'NuevaClave2@',
            ])
            ->call('guardar')
            ->assertHasNoFormErrors();

        $evento = SecurityEvent::where('user_id', $user->id)->first();

        $this->assertNotNull($evento, 'El cambio tiene que quedar registrado en security_events.');
        $this->assertSame(SecurityEvent::PASSWORD_CHANGED, $evento->event);
        $this->assertSame(SecurityEvent::CONTEXT_VOLUNTARY, $evento->context);
        $this->assertSame($user->tenant_id, $evento->tenant_id);
    }

    public function test_no_cambia_ni_registra_si_la_actual_es_incorrecta(): void
    {
        $user = $this->usuario();
        $this->actingAs($user);

        Livewire::test(CambiarPassword::class)
            ->fillForm([
                'current_password' => 'NoEsLaClave1!',
                'new_password' => 'NuevaClave2@',
                'new_password_confirmation' => 'NuevaClave2@',
            ])
            ->call('guardar')
            ->assertHasFormErrors(['current_password']);

        $this->assertTrue(Hash::check('Password1!', $user->fresh()->password), 'La clave no debe cambiar.');
        $this->assertSame(
            0,
            SecurityEvent::where('user_id', $user->id)->count(),
            'Un intento fallido no es un cambio: no debe dejar evento de seguridad.'
        );
    }

    public function test_la_nueva_contrasena_tiene_que_cumplir_la_regla(): void
    {
        $user = $this->usuario();
        $this->actingAs($user);

        Livewire::test(CambiarPassword::class)
            ->fillForm([
                'current_password' => 'Password1!',
                'new_password' => 'debil',
                'new_password_confirmation' => 'debil',
            ])
            ->call('guardar')
            ->assertHasFormErrors(['new_password']);

        $this->assertTrue(Hash::check('Password1!', $user->fresh()->password));
    }

    public function test_el_cambio_obligatorio_del_primer_acceso_se_registra_como_forzado(): void
    {
        $user = $this->usuario(['force_password_change' => true]);
        $this->actingAs($user);

        Livewire::test(PasswordChange::class)
            ->fillForm([
                'current_password' => 'Password1!',
                'new_password' => 'NuevaClave2@',
                'new_password_confirmation' => 'NuevaClave2@',
            ])
            ->call('changePassword');

        $evento = SecurityEvent::where('user_id', $user->id)->first();

        $this->assertNotNull($evento, 'El cambio obligatorio también tiene que quedar registrado.');
        $this->assertSame(SecurityEvent::PASSWORD_CHANGED, $evento->event);
        $this->assertSame(SecurityEvent::CONTEXT_FORCED, $evento->context);
        $this->assertFalse((bool) $user->fresh()->force_password_change);
    }

    public function test_los_eventos_de_seguridad_no_viven_en_la_tabla_de_telemetria_de_uso(): void
    {
        $user = $this->usuario();
        $this->actingAs($user);

        Livewire::test(CambiarPassword::class)
            ->fillForm([
                'current_password' => 'Password1!',
                'new_password' => 'NuevaClave2@',
                'new_password_confirmation' => 'NuevaClave2@',
            ])
            ->call('guardar');

        // La frontera es el punto de todo esto: `usage_events` mide uso del producto (pageviews) y
        // `security_events` audita acciones sobre la cuenta. Si se mezclan, se rompen las dos.
        $this->assertSame(
            0,
            \App\Models\UsageEvent::where('user_id', $user->id)->count(),
            'Un cambio de contraseña no es una pageview: no debe ensuciar la telemetría de uso.'
        );
    }
}
