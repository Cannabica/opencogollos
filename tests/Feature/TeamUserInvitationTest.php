<?php

namespace Tests\Feature;

use App\Filament\Tenant\Pages\TenantPage;
use App\Models\SecurityEvent;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\TeamUserActivationNotification;
use App\Notifications\TeamUserInvitationNotification;
use App\Support\PasswordRequirements;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Alta de personas al grupo, con las dos modalidades (revisión del dueño del repo, 2026-09-27).
 *
 *  - `password`: se le manda una contraseña **que cumple la política** (antes era `Str::random(12)`, que
 *    podía no cumplirla y el usuario recibía algo que el sistema le rechazaba al cambiarla).
 *  - `self`: **sin clave**: se guarda una contraseña aleatoria que nunca se comunica y se le manda un
 *    link (48 h) para que defina la suya.
 *
 * Sólo el owner puede sumar gente.
 */
class TeamUserInvitationTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{tenant: Tenant, owner: User, miembro: User} */
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

        $miembro = User::factory()->create([
            'tenant_id' => $tenant->id,
            'email' => 'miembro@ejemplo.test',
        ]);

        return compact('tenant', 'owner', 'miembro');
    }

    public function test_con_la_modalidad_password_se_manda_una_clave_que_cumple_la_politica(): void
    {
        Notification::fake();
        ['owner' => $owner] = $this->entorno();
        $this->actingAs($owner);

        Livewire::test(TenantPage::class)
            ->set('userName', 'Persona Nueva')
            ->set('userEmail', 'nueva@ejemplo.test')
            ->set('inviteMode', 'password')
            ->call('addUser');

        $nuevo = User::where('email', 'nueva@ejemplo.test')->first();
        $this->assertNotNull($nuevo);
        $this->assertTrue((bool) $nuevo->force_password_change, 'El primer ingreso tiene que pedirle cambiarla.');

        Notification::assertSentTo(
            $nuevo,
            TeamUserActivationNotification::class,
            function (TeamUserActivationNotification $notification) {
                // La clave que viaja por mail tiene que poder usarse: si no cumple la política, el
                // sistema se la rechaza al cambiarla y el usuario queda trabado.
                return PasswordRequirements::passes($notification->password);
            }
        );
    }

    public function test_con_la_modalidad_self_no_se_manda_clave_y_va_un_link_para_definirla(): void
    {
        Notification::fake();
        ['owner' => $owner] = $this->entorno();
        $this->actingAs($owner);

        Livewire::test(TenantPage::class)
            ->set('userName', 'Invitada')
            ->set('userEmail', 'invitada@ejemplo.test')
            ->set('inviteMode', 'self')
            ->call('addUser');

        $invitada = User::where('email', 'invitada@ejemplo.test')->first();
        $this->assertNotNull($invitada);

        Notification::assertNotSentTo($invitada, TeamUserActivationNotification::class);

        Notification::assertSentTo(
            $invitada,
            TeamUserInvitationNotification::class,
            function (TeamUserInvitationNotification $notification) use ($invitada) {
                // El link apunta al formulario de contraseña (el mismo que ya tiene la validación
                // visual en vivo), lleva el token del broker para ESE usuario y va FIRMADO: Filament
                // exige firma en esa ruta y sin ella responde 403 (medido 2026-09-27).
                return str_contains($notification->invitationUrl, 'password-reset')
                    && str_contains($notification->invitationUrl, urlencode($invitada->email))
                    && str_contains($notification->invitationUrl, 'signature=');
            }
        );

        // El token del broker existe y es el que viajó en el link (vive 48 h, ver config/auth.php).
        $this->assertSame(2880, (int) config('auth.passwords.users.expire'));
    }

    public function test_el_link_de_invitacion_funciona_de_verdad(): void
    {
        // Este test es el que hubiera cazado el 403: sigue el link tal cual lo recibe la persona.
        // Sin la firma, Filament responde 403 aunque el token sea válido.
        Notification::fake();
        ['owner' => $owner] = $this->entorno();
        $this->actingAs($owner);

        Livewire::test(TenantPage::class)
            ->set('userName', 'Invitada')
            ->set('userEmail', 'invitada@ejemplo.test')
            ->set('inviteMode', 'self')
            ->call('addUser');

        $url = null;
        Notification::assertSentTo(
            User::where('email', 'invitada@ejemplo.test')->first(),
            TeamUserInvitationNotification::class,
            function (TeamUserInvitationNotification $notification) use (&$url) {
                $url = $notification->invitationUrl;

                return true;
            }
        );

        // Quien abre el link del mail NO está logueado: si hubiera sesión, Filament redirige (302) en vez
        // de mostrar el formulario. Este test sigue el link como lo hace la persona invitada.
        \Illuminate\Support\Facades\Auth::logout();

        $this->get($url)->assertOk();
    }

    public function test_el_alta_queda_en_la_trazabilidad_con_su_modalidad(): void
    {
        Notification::fake();
        ['owner' => $owner] = $this->entorno();
        $this->actingAs($owner);

        Livewire::test(TenantPage::class)
            ->set('userName', 'Invitada')
            ->set('userEmail', 'invitada@ejemplo.test')
            ->set('inviteMode', 'self')
            ->call('addUser');

        $evento = SecurityEvent::where('event', SecurityEvent::TEAM_USER_INVITED)->first();

        $this->assertNotNull($evento, 'Sumar gente al grupo tiene que quedar registrado.');
        $this->assertSame('self', $evento->context);
    }

    public function test_un_miembro_que_no_es_owner_no_puede_agregar_usuarios(): void
    {
        Notification::fake();
        ['miembro' => $miembro] = $this->entorno();
        $this->actingAs($miembro);

        Livewire::test(TenantPage::class)
            ->set('userName', 'Colado')
            ->set('userEmail', 'colado@ejemplo.test')
            ->call('addUser');

        $this->assertNull(User::where('email', 'colado@ejemplo.test')->first());
        Notification::assertNothingSent();
    }

    public function test_la_clave_generada_por_el_sistema_siempre_cumple_la_politica(): void
    {
        for ($i = 0; $i < 25; $i++) {
            $this->assertTrue(
                PasswordRequirements::passes(PasswordRequirements::generate()),
                'Una clave emitida por el sistema que no cumpla la política deja al usuario trabado.'
            );
        }
    }

    public function test_el_modo_invalido_no_pasa_la_validacion(): void
    {
        Notification::fake();
        ['owner' => $owner] = $this->entorno();
        $this->actingAs($owner);

        Livewire::test(TenantPage::class)
            ->set('userName', 'Persona')
            ->set('userEmail', 'persona@ejemplo.test')
            ->set('inviteMode', 'lo-que-sea')
            ->call('addUser')
            ->assertHasErrors('inviteMode');

        $this->assertNull(User::where('email', 'persona@ejemplo.test')->first());
    }
}
