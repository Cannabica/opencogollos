<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El login de la API no puede aceptar intentos ilimitados.
 *
 * `POST /api/auth/login` lo registra el paquete `rupadana/filament-api-service` y quedaba SIN
 * middleware (no hereda el `throttle:api` del grupo). Medido el 2026-09-14: **65 intentos
 * fallidos seguidos → 65 x 401, ningún 429**. El login del panel, con la misma credencial
 * inválida, corta a los 5 (ver TenantLoginCredentialsTest).
 *
 * El throttle se cuelga desde RouteServiceProvider con el limiter `api-login`
 * (20/min por IP + 5/min por email+IP). Este archivo es la garantía de que sigue puesto.
 */
class ApiLoginThrottleTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_login_de_la_api_corta_tras_cinco_intentos_sobre_la_misma_cuenta(): void
    {
        $codigos = [];

        for ($intento = 1; $intento <= 8; $intento++) {
            $codigos[] = $this->postJson('/api/auth/login', [
                'email' => 'fuerza-bruta@ejemplo.test',
                'password' => 'intento-' . $intento,
            ])->getStatusCode();
        }

        $this->assertContains(
            429,
            $codigos,
            'El login de la API aceptó 8 intentos fallidos sin cortar: quedó sin throttle. Códigos: ' . implode(',', $codigos)
        );
    }

    public function test_un_login_valido_de_la_api_sigue_funcionando(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'password' => bcrypt('password'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertCreated()->assertJsonPath('success', true);
        $this->assertNotEmpty($response->json('token'));
    }
}
