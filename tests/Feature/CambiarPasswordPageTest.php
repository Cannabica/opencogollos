<?php

namespace Tests\Feature;

use App\Filament\Tenant\Pages\CambiarPassword;
use App\Filament\Tenant\Pages\PasswordChange;
use App\Models\SecurityEvent;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\PasswordChangedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * "Cambiar contraseña" (página propia) + su trazabilidad y sus avisos.
 *
 * Tres reglas del producto (revisión del dueño del repo, 2026-09-27):
 *  1. El cambio vive en su propia página (se llega desde "Mi Grupo", no del menú).
 *  2. Cada cambio queda en `security_events` — tabla propia, separada de la telemetría de uso.
 *  3. Después de cambiar la contraseña **se cierra la sesión** (la abierta usaba la credencial vieja)
 *     y **se avisa a la cuenta por mail** (el aviso es lo único que hace visible un cambio no
 *     autorizado). El aviso por Telegram, si el grupo tiene chat, se cubre en
 *     `PasswordChangeNotificationTest`.
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
        Notification::fake();
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

    public function test_despues_de_cambiar_la_contrasena_se_cierra_la_sesion(): void
    {
        Notification::fake();
        $user = $this->usuario();
        $this->actingAs($user);

        $this->assertAuthenticated();

        Livewire::test(CambiarPassword::class)
            ->fillForm([
                'current_password' => 'Password1!',
                'new_password' => 'NuevaClave2@',
                'new_password_confirmation' => 'NuevaClave2@',
            ])
            ->call('guardar')
            ->assertRedirect(route('filament.tenant.auth.login'));

        // La sesión se abrió con la credencial vieja: tiene que volver a entrar con la nueva.
        $this->assertGuest();
    }

    public function test_se_avisa_por_mail_cuando_el_cambio_sale_bien(): void
    {
        Notification::fake();
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

        Notification::assertSentTo($user, PasswordChangedNotification::class);
    }

    public function test_el_cambio_voluntario_queda_registrado_en_seguridad(): void
    {
        Notification::fake();
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

    public function test_no_cambia_no_registra_ni_avisa_si_la_actual_es_incorrecta(): void
    {
        Notification::fake();
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
        Notification::assertNothingSent();
        $this->assertAuthenticated();
    }

    public function test_la_nueva_contrasena_tiene_que_cumplir_la_regla(): void
    {
        Notification::fake();
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
        Notification::fake();
        $user = $this->usuario(['force_password_change' => true]);
        $this->actingAs($user);

        Livewire::test(PasswordChange::class)
            ->fillForm([
                'current_password' => 'Password1!',
                'new_password' => 'NuevaClave2@',
                'new_password_confirmation' => 'NuevaClave2@',
            ])
            ->call('changePassword')
            ->assertRedirect(route('filament.tenant.auth.login'));

        $evento = SecurityEvent::where('user_id', $user->id)->first();

        $this->assertNotNull($evento, 'El cambio obligatorio también tiene que quedar registrado.');
        $this->assertSame(SecurityEvent::PASSWORD_CHANGED, $evento->event);
        $this->assertSame(SecurityEvent::CONTEXT_FORCED, $evento->context);
        $this->assertFalse((bool) $user->fresh()->force_password_change);

        // El flujo obligatorio también avisa y también cierra la sesión.
        Notification::assertSentTo($user, PasswordChangedNotification::class);
        $this->assertGuest();
    }

    public function test_los_eventos_de_seguridad_no_viven_en_la_tabla_de_telemetria_de_uso(): void
    {
        Notification::fake();
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
