<?php

namespace Tests\Feature;

use App\Filament\Pages\Dashboard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * El selector de lugar del panel de inicio tiene que filtrar de verdad: al elegir
 * un lugar, la pantalla muestra solo ese y deja de mostrar los demás.
 */
class DashboardFiltroLugarTest extends TestCase
{
    use RefreshDatabase;

    private function crearTenant(string $nombre): int
    {
        return DB::table('tenants')->insertGetId([
            'name' => $nombre,
            'email' => strtolower(str_replace(' ', '.', $nombre)) . '@test.local',
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function crearUsuario(int $tenantId): User
    {
        $id = DB::table('users')->insertGetId([
            'name' => 'Cultivador',
            'email' => 'cultivador' . $tenantId . '@test.local',
            'password' => bcrypt('password'),
            'tenant_id' => $tenantId,
            'email_verified_at' => now(),
            'force_password_change' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return User::find($id);
    }

    private function crearLugar(int $tenantId, string $nombre, int $plantas = 0): int
    {
        $id = DB::table('indoors')->insertGetId([
            'name' => $nombre,
            'large' => 100,
            'width' => 100,
            'height' => 180,
            'tenant_id' => $tenantId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($plantas > 0) {
            $seedId = DB::table('seeds')->insertGetId([
                'name' => 'Semilla',
                'seed_type' => 'Automatica',
                'ratio_thc' => 10,
                'ratio_cbd' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            for ($i = 1; $i <= $plantas; $i++) {
                DB::table('plants')->insert([
                    'name' => $nombre . ' planta ' . $i,
                    'seed_id' => $seedId,
                    'indoor_id' => $id,
                    'state' => 'Etapa de Vegetativa',
                    'flowerpot' => 'Geotextiles',
                    'capacity' => 10,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        return $id;
    }

    public function test_el_filtro_por_lugar_deja_solo_el_lugar_elegido(): void
    {
        $tenant = $this->crearTenant('Con Dos Lugares');
        $usuario = $this->crearUsuario($tenant);
        $elegido = $this->crearLugar($tenant, 'Carpa Norte', 2);
        $this->crearLugar($tenant, 'Carpa Sur', 3);

        $this->actingAs($usuario);

        // Se miran los nombres de las PLANTAS: el nombre del lugar aparece también en las
        // opciones del selector, así que buscarlo en todo el HTML daría un falso positivo.
        Livewire::test(Dashboard::class)
            ->assertSee('Carpa Norte planta 1')
            ->assertSee('Carpa Sur planta 1')
            ->set('filters.indoor', (string) $elegido)
            ->assertSee('Carpa Norte planta 1')
            ->assertDontSee('Carpa Sur planta 1');
    }

    public function test_el_lugar_muestra_cuantas_plantas_tiene(): void
    {
        $tenant = $this->crearTenant('Con Conteo');
        $usuario = $this->crearUsuario($tenant);
        $this->crearLugar($tenant, 'Patio', 4);

        $this->actingAs($usuario);

        Livewire::test(Dashboard::class)
            ->assertSee('Patio')
            ->assertSee('4 plantas');
    }

    public function test_con_un_solo_lugar_no_se_ofrece_el_selector(): void
    {
        $tenant = $this->crearTenant('Con Uno');
        $usuario = $this->crearUsuario($tenant);
        $this->crearLugar($tenant, 'Unico Lugar', 2);

        $this->actingAs($usuario);

        Livewire::test(Dashboard::class)
            ->assertSee('Unico Lugar')
            ->assertDontSee('Todos los lugares');
    }
}
