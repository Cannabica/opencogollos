<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Guarda de la poda de la superficie Breeze (2026-09-14).
 *
 * Se eliminó todo el scaffold que duplicaba (y en algunos casos contradecía) al panel Filament:
 * login, registro, reset, confirmación de password, verificación de email y el perfil de Breeze
 * —este último con borrado de cuenta—. Este archivo existe para que **no vuelvan por accidente**:
 * si alguien re-agrega alguna de esas rutas sin decidirlo, el test falla.
 *
 * El reemplazo in-house con Filament está especificado en las tarjetas T10.x del board.
 */
class BreezeSurfaceRemovedTest extends TestCase
{
    use RefreshDatabase;

    private function tenantUser(): User
    {
        $tenant = Tenant::factory()->create();

        return User::factory()->create(['tenant_id' => $tenant->id]);
    }

    public function test_las_rutas_del_scaffold_ya_no_existen(): void
    {
        $user = $this->tenantUser();

        $rutas = [
            ['get', '/profile'],
            ['get', '/verify-email'],
            ['get', '/confirm-password'],
            ['put', '/password'],
            ['post', '/register'],
            ['post', '/forgot-password'],
        ];

        foreach ($rutas as [$metodo, $uri]) {
            $this->actingAs($user)->{$metodo}($uri)
                ->assertNotFound("La ruta {$metodo} {$uri} volvió a existir: era parte del scaffold de Breeze que se podó.");
        }
    }

    public function test_ya_no_se_puede_borrar_la_cuenta_por_la_via_vieja(): void
    {
        $user = $this->tenantUser();

        $this->actingAs($user)->delete('/profile')->assertNotFound();

        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_el_dashboard_viejo_redirige_al_panel_del_tenant(): void
    {
        $this->actingAs($this->tenantUser())
            ->get('/dashboard')
            ->assertRedirect('/tenant');
    }

    public function test_la_superficie_real_de_auth_sigue_arriba(): void
    {
        $this->get('/tenant/login')->assertOk();
        $this->get('/superadmin/login')->assertOk();
    }
}
