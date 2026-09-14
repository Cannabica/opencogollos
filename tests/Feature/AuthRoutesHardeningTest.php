<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Garantías de las rutas de auth que quedaron en routes/auth.php.
 *
 * Se agregó junto con el acotamiento de vectores (throttle en las rutas que
 * validan `current_password`). Prueba tres cosas que antes nadie miraba:
 *   1. que el throttle corta de verdad (no sólo que el middleware esté escrito),
 *   2. que las rutas siguen funcionando (un throttle mal puesto rompería el flujo),
 *   3. que la superficie de verify-email sigue viva (se evaluó sacarla y se decidió
 *      no hacerlo: la usa /profile, que es una ruta viva de este repo).
 */
class AuthRoutesHardeningTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $password = 'password'): User
    {
        $tenant = Tenant::factory()->create();

        return User::factory()->create([
            'tenant_id' => $tenant->id,
            'password' => bcrypt($password),
        ]);
    }

    public function test_confirm_password_get_renderiza(): void
    {
        $this->actingAs($this->user())->get('/confirm-password')->assertOk();
    }

    public function test_confirm_password_post_con_password_correcta_confirma(): void
    {
        $user = $this->user();

        $this->actingAs($user)
            ->post('/confirm-password', ['password' => 'password'])
            ->assertRedirect();

        $this->assertNotNull(session('auth.password_confirmed_at'));
    }

    public function test_password_update_funciona_con_la_password_correcta(): void
    {
        $user = $this->user();

        $this->actingAs($user)->put('/password', [
            'current_password' => 'password',
            'password' => 'nueva-password-123',
            'password_confirmation' => 'nueva-password-123',
        ])->assertRedirect();

        $this->assertTrue(Hash::check('nueva-password-123', $user->fresh()->password));
    }

    public function test_confirm_password_corta_tras_seis_intentos(): void
    {
        $user = $this->user();

        $codigos = [];
        for ($i = 1; $i <= 7; $i++) {
            $codigos[] = $this->actingAs($user)
                ->post('/confirm-password', ['password' => 'incorrecta'])->getStatusCode();
        }

        fwrite(STDERR, "\nCODIGOS confirm-password: ".implode(',', $codigos)."\n");
        $this->assertContains(429, $codigos, 'El throttle:6,1 tiene que cortar en algún intento.');
    }

    public function test_verify_email_renderiza_para_usuario_sin_verificar(): void
    {
        $user = $this->user();
        $user->forceFill(['email_verified_at' => null])->save();

        $this->actingAs($user->fresh())->get('/verify-email')->assertOk();
    }
}
