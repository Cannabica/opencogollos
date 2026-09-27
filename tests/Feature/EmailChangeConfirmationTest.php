<?php

namespace Tests\Feature;

use App\Models\EmailChangeRequest;
use App\Models\SecurityEvent;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\EmailChangedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Cambio de email — SEGUNDA mitad del doble opt-in: recién el link lo aplica.
 *
 * Al confirmar: cambia `users.email`, queda en `security_events`, se avisa a la dirección VIEJA y a
 * la NUEVA, y **se cierra la sesión** (la credencial cambió: hay que volver a entrar con la nueva).
 */
class EmailChangeConfirmationTest extends TestCase
{
    use RefreshDatabase;

    private function pendiente(User $user, string $nuevoEmail, ?string $expiraEn = null): string
    {
        ['token' => $token] = EmailChangeRequest::createFor($user, $nuevoEmail);

        if ($expiraEn === 'vencido') {
            $solicitud = EmailChangeRequest::where('user_id', $user->id)->first();
            $solicitud->update(['expires_at' => now()->subMinute()]);
        }

        return $token;
    }

    private function usuario(string $email = 'viejo@ejemplo.test'): User
    {
        $tenant = Tenant::factory()->create(['active' => true]);

        return User::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Nombre',
            'email' => $email,
            'password' => Hash::make('Password1!'),
        ]);
    }

    public function test_confirmar_aplica_el_cambio_y_registra_el_evento(): void
    {
        Notification::fake();
        $user = $this->usuario();
        $token = $this->pendiente($user, 'nuevo@ejemplo.test');

        $this->get(route('tenant.email.confirm', ['token' => $token]))
            ->assertRedirect(route('filament.tenant.auth.login'));

        $this->assertSame('nuevo@ejemplo.test', $user->fresh()->email);

        $evento = SecurityEvent::where('user_id', $user->id)->where('event', SecurityEvent::EMAIL_CHANGED)->first();
        $this->assertNotNull($evento, 'El cambio de email tiene que quedar en la trazabilidad de seguridad.');
    }

    public function test_al_confirmar_se_avisa_a_las_dos_direcciones(): void
    {
        Notification::fake();
        $user = $this->usuario();
        $token = $this->pendiente($user, 'nuevo@ejemplo.test');

        $this->get(route('tenant.email.confirm', ['token' => $token]));

        foreach (['viejo@ejemplo.test', 'nuevo@ejemplo.test'] as $direccion) {
            Notification::assertSentOnDemand(
                EmailChangedNotification::class,
                fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === $direccion
            );
        }
    }

    public function test_al_confirmar_se_cierra_la_sesion(): void
    {
        Notification::fake();
        $user = $this->usuario();
        $token = $this->pendiente($user, 'nuevo@ejemplo.test');

        $this->actingAs($user)->get(route('tenant.email.confirm', ['token' => $token]));

        $this->assertGuest();
    }

    public function test_un_token_invalido_no_cambia_nada(): void
    {
        Notification::fake();
        $user = $this->usuario();

        $this->get(route('tenant.email.confirm', ['token' => 'token-inventado']))
            ->assertRedirect(route('filament.tenant.auth.login'));

        $this->assertSame('viejo@ejemplo.test', $user->fresh()->email);
        $this->assertSame(0, SecurityEvent::count());
    }

    public function test_un_token_vencido_no_cambia_nada(): void
    {
        Notification::fake();
        $user = $this->usuario();
        $token = $this->pendiente($user, 'nuevo@ejemplo.test', 'vencido');

        $this->get(route('tenant.email.confirm', ['token' => $token]));

        $this->assertSame('viejo@ejemplo.test', $user->fresh()->email);
        $this->assertSame(0, SecurityEvent::count());
    }

    public function test_un_token_ya_usado_no_sirve_dos_veces(): void
    {
        Notification::fake();
        $user = $this->usuario();
        $token = $this->pendiente($user, 'nuevo@ejemplo.test');

        $this->get(route('tenant.email.confirm', ['token' => $token]));
        $this->assertSame('nuevo@ejemplo.test', $user->fresh()->email);

        // Volver a abrir el link no puede re-aplicar nada (ni cambiar el email a otra cosa).
        $this->get(route('tenant.email.confirm', ['token' => $token]));

        $this->assertSame('nuevo@ejemplo.test', $user->fresh()->email);
        $this->assertSame(1, SecurityEvent::where('event', SecurityEvent::EMAIL_CHANGED)->count());
    }

    public function test_si_la_direccion_nueva_se_ocupo_mientras_tanto_no_se_aplica(): void
    {
        Notification::fake();
        $user = $this->usuario();
        $token = $this->pendiente($user, 'nuevo@ejemplo.test');

        // Otra cuenta se queda con esa dirección antes de la confirmación.
        $this->usuario('nuevo@ejemplo.test');

        $this->get(route('tenant.email.confirm', ['token' => $token]))
            ->assertRedirect(route('filament.tenant.auth.login'));

        $this->assertSame('viejo@ejemplo.test', $user->fresh()->email);
        $this->assertSame(0, SecurityEvent::count());
    }
}
