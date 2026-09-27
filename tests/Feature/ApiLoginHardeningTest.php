<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\ApiLoginController;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * T10.6 — endurecer el login de la API.
 *
 * El endpoint `POST /api/auth/login` no es una ruta de la app: lo registra el paquete
 * `rupadana/filament-api-service`. En T2.2 se le colgó el throttle (`ApiLoginThrottleTest`);
 * acá se cubre el resto del endurecimiento, que el paquete no hacía:
 *
 *   - el token se emitía con la ability comodín `['*']`;
 *   - un tenant **inactivo** obtenía token igual, con credenciales válidas.
 *
 * El reemplazo de la acción vive en `RouteServiceProvider`; este archivo es la garantía de que
 * un upgrade del paquete no lo revierta en silencio.
 */
class ApiLoginHardeningTest extends TestCase
{
    use RefreshDatabase;

    private function hacerLogin(User $user, string $password = 'password')
    {
        return $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => $password,
        ]);
    }

    public function test_un_tenant_activo_sigue_obteniendo_token(): void
    {
        $tenant = Tenant::factory()->create(['active' => true]);
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'password' => bcrypt('password'),
        ]);

        $this->hacerLogin($user)
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['token']);
    }

    public function test_un_tenant_inactivo_no_obtiene_token(): void
    {
        $tenant = Tenant::factory()->create(['active' => false]);
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'password' => bcrypt('password'),
        ]);

        $response = $this->hacerLogin($user);

        $response->assertForbidden();
        $this->assertEmpty($response->json('token'), 'El tenant inactivo no debe recibir token.');
        $this->assertSame(0, $user->tokens()->count(), 'No debe quedar ningún token creado.');
    }

    public function test_el_token_no_trae_la_ability_comodin(): void
    {
        $tenant = Tenant::factory()->create(['active' => true]);
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'password' => bcrypt('password'),
        ]);

        $this->hacerLogin($user)->assertCreated();

        /** @var \Laravel\Sanctum\PersonalAccessToken|null $token */
        $token = $user->tokens()->first();
        $this->assertNotNull($token);
        $this->assertNotContains('*', $token->abilities, 'El token no puede venir con todas las abilities.');
        $this->assertSame(ApiLoginController::ABILITIES, $token->abilities);
    }
}
