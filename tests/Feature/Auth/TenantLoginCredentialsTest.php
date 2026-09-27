<?php

namespace Tests\Feature\Auth;

use App\Models\Tenant;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Pages\Auth\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Credenciales contra la superficie de login REAL (panel Filament del tenant).
 *
 * Reemplaza al "verde falso" `AuthenticationTest::test_users_can_not_authenticate_with_invalid_password`:
 * ese test posteaba a `POST /login` — una ruta del scaffold de Breeze que en este repo está apagada —
 * y sólo afirmaba `assertGuest()`. Como la ruta no existe, el usuario siempre queda como invitado y el
 * test **pasaba igual aunque el login real aceptara cualquier contraseña**: no probaba nada.
 *
 * Acá el peligro se prueba contra la superficie real y en las DOS direcciones:
 *   - una credencial inválida NO autentica (el peligro),
 *   - una credencial válida SÍ autentica (sin esto el test volvería a poder pasar en falso: si el login
 *     se rompe, todo queda como invitado y un `assertGuest()` suelto seguiría verde),
 *   - y el intento repetido se bloquea (el vector real de fuerza bruta / credential stuffing).
 */
class TenantLoginCredentialsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('tenant'));
    }

    private function tenantUser(string $password = 'password'): User
    {
        $tenant = Tenant::factory()->create();

        return User::factory()->create([
            'tenant_id' => $tenant->id,
            'password' => bcrypt($password),
        ]);
    }

    public function test_una_password_incorrecta_no_autentica_en_el_login_del_panel(): void
    {
        $user = $this->tenantUser();

        Livewire::test(Login::class)
            ->fillForm(['email' => $user->email, 'password' => 'password-incorrecta'])
            ->call('authenticate')
            ->assertHasErrors();

        $this->assertGuest();
    }

    public function test_la_password_correcta_autentica_en_el_login_del_panel(): void
    {
        $user = $this->tenantUser('password');

        Livewire::test(Login::class)
            ->fillForm(['email' => $user->email, 'password' => 'password'])
            ->call('authenticate');

        $this->assertAuthenticatedAs($user->fresh());
    }

    public function test_un_email_inexistente_no_autentica(): void
    {
        Livewire::test(Login::class)
            ->fillForm(['email' => 'nadie@ejemplo.test', 'password' => 'password'])
            ->call('authenticate')
            ->assertHasErrors();

        $this->assertGuest();
    }

    public function test_el_login_se_bloquea_tras_cinco_intentos_fallidos(): void
    {
        $user = $this->tenantUser('password');

        for ($intento = 0; $intento < 5; $intento++) {
            Livewire::test(Login::class)
                ->fillForm(['email' => $user->email, 'password' => 'password-incorrecta'])
                ->call('authenticate');
        }

        // Sexto intento, esta vez con la contraseña correcta: tiene que seguir afuera.
        Livewire::test(Login::class)
            ->fillForm(['email' => $user->email, 'password' => 'password'])
            ->call('authenticate');

        // Con la contraseña CORRECTA y todavía afuera: eso es el bloqueo por intentos.
        $this->assertGuest();
    }
}
