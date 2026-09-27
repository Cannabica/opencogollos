<?php

namespace Tests\Feature;

use App\Filament\Tenant\Pages\Cuenta;
use App\Models\EmailChangeRequest;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\EmailChangeConfirmationNotification;
use App\Notifications\EmailChangeRequestedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Cambio de email — PRIMERA mitad del doble opt-in: pedirlo NO lo aplica.
 *
 * El email es la credencial de login. Reglas del producto (2026-09-27):
 *  - pide la contraseña actual (es un cambio de alto riesgo),
 *  - manda el link de confirmación a la dirección NUEVA,
 *  - avisa a la dirección VIEJA (es lo único que hace visible un cambio no autorizado),
 *  - **el email no cambia** hasta que se abra el link (ver EmailChangeConfirmationTest).
 */
class EmailChangeRequestTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(array $extra = []): User
    {
        $tenant = Tenant::factory()->create(['active' => true]);

        return User::factory()->create(array_merge([
            'tenant_id' => $tenant->id,
            'name' => 'Nombre Viejo',
            'email' => 'viejo@ejemplo.test',
            'password' => Hash::make('Password1!'),
        ], $extra));
    }

    /** @return array<string, string> */
    private function form(User $user, string $email, string $password = 'Password1!', ?string $name = null): array
    {
        return [
            'name' => $name ?? $user->name,
            'email' => $email,
            'current_password' => $password,
        ];
    }

    public function test_pedir_el_cambio_no_cambia_el_email(): void
    {
        Notification::fake();
        $user = $this->usuario();
        $this->actingAs($user);

        Livewire::test(Cuenta::class)
            ->fillForm($this->form($user, 'nuevo@ejemplo.test'))
            ->call('guardar')
            ->assertHasNoFormErrors();

        $this->assertSame(
            'viejo@ejemplo.test',
            $user->fresh()->email,
            'El email es la credencial: no puede cambiar hasta que se confirme.'
        );
        $this->assertSame(1, EmailChangeRequest::where('user_id', $user->id)->count());
    }

    public function test_el_link_va_a_la_direccion_nueva_y_el_aviso_a_la_vieja(): void
    {
        Notification::fake();
        $user = $this->usuario();
        $this->actingAs($user);

        Livewire::test(Cuenta::class)
            ->fillForm($this->form($user, 'nuevo@ejemplo.test'))
            ->call('guardar')
            ->assertHasNoFormErrors();

        Notification::assertSentOnDemand(
            EmailChangeConfirmationNotification::class,
            fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === 'nuevo@ejemplo.test'
        );

        Notification::assertSentOnDemand(
            EmailChangeRequestedNotification::class,
            fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === 'viejo@ejemplo.test'
        );
    }

    public function test_el_token_que_viaja_por_mail_no_se_guarda_en_claro(): void
    {
        Notification::fake();
        $user = $this->usuario();
        $this->actingAs($user);

        Livewire::test(Cuenta::class)
            ->fillForm($this->form($user, 'nuevo@ejemplo.test'))
            ->call('guardar');

        $token = null;
        Notification::assertSentOnDemand(
            EmailChangeConfirmationNotification::class,
            function ($notification) use (&$token) {
                $token = $notification->token;

                return true;
            }
        );

        $solicitud = EmailChangeRequest::where('user_id', $user->id)->first();

        $this->assertNotNull($token);
        $this->assertNotSame($token, $solicitud->token_hash, 'En la base va el hash, no el token.');
        $this->assertSame(EmailChangeRequest::hash($token), $solicitud->token_hash);
    }

    public function test_sin_contrasena_no_se_puede_pedir_el_cambio(): void
    {
        Notification::fake();
        $user = $this->usuario();
        $this->actingAs($user);

        Livewire::test(Cuenta::class)
            ->fillForm([
                'name' => $user->name,
                'email' => 'nuevo@ejemplo.test',
                'current_password' => '',
            ])
            ->call('guardar')
            ->assertHasFormErrors(['current_password']);

        $this->assertSame('viejo@ejemplo.test', $user->fresh()->email);
        $this->assertSame(0, EmailChangeRequest::where('user_id', $user->id)->count());
        Notification::assertNothingSent();
    }

    public function test_con_la_contrasena_incorrecta_no_se_puede_pedir_el_cambio(): void
    {
        Notification::fake();
        $user = $this->usuario();
        $this->actingAs($user);

        Livewire::test(Cuenta::class)
            ->fillForm($this->form($user, 'nuevo@ejemplo.test', 'ClaveIncorrecta1!'))
            ->call('guardar')
            ->assertHasFormErrors(['current_password']);

        $this->assertSame('viejo@ejemplo.test', $user->fresh()->email);
        $this->assertSame(0, EmailChangeRequest::where('user_id', $user->id)->count());
        Notification::assertNothingSent();
    }

    public function test_cambiar_solo_el_nombre_no_pide_contrasena_ni_manda_mails(): void
    {
        Notification::fake();
        $user = $this->usuario();
        $this->actingAs($user);

        Livewire::test(Cuenta::class)
            ->fillForm([
                'name' => 'Nombre Nuevo',
                'email' => 'viejo@ejemplo.test',
                'current_password' => '',
            ])
            ->call('guardar')
            ->assertHasNoFormErrors();

        $this->assertSame('Nombre Nuevo', $user->fresh()->name);
        $this->assertSame(0, EmailChangeRequest::where('user_id', $user->id)->count());
        Notification::assertNothingSent();
    }

    public function test_un_segundo_pedido_descarta_el_anterior(): void
    {
        Notification::fake();
        $user = $this->usuario();
        $this->actingAs($user);

        Livewire::test(Cuenta::class)->fillForm($this->form($user, 'primero@ejemplo.test'))->call('guardar');
        Livewire::test(Cuenta::class)->fillForm($this->form($user, 'segundo@ejemplo.test'))->call('guardar');

        $solicitudes = EmailChangeRequest::where('user_id', $user->id)->get();

        $this->assertCount(1, $solicitudes, 'Vale el último pedido: no pueden quedar links viejos dando vueltas.');
        $this->assertSame('segundo@ejemplo.test', $solicitudes->first()->new_email);
    }
}
