<?php

namespace Tests\Feature;

use App\Models\Action;
use App\Models\ActionType;
use App\Models\Indoor;
use App\Models\Plant;
use App\Models\Scopes\TenantScope;
use App\Models\Seed;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SEMÁNTICA DEL `TenantScope` (hotfix 2026-09-28).
 *
 * Antes: sin usuario logueado hacía `return` — o sea NO filtraba nada. El webhook de Telegram no tiene
 * sesión, así que ese "sin filtro" era la puerta de la fuga entre grupos. Ahora:
 *
 *   - sin contexto y sin usuario (HTTP) → NO devuelve nada (falla cerrado);
 *   - con contexto explícito (`TenantContext::use()`) → ese tenant (lo que usa el bot);
 *   - usuario sin tenant (superadmin) → ve todo (es su trabajo, y el panel lo necesita);
 *   - contexto de servicio (`TenantContext::useAll()`) → sin filtro (bot de admin, consola).
 *
 * Los tests corren en consola, pero `TenantContext::resolve()` excluye explícitamente a los tests
 * (`runningUnitTests()`), así que acá se mide la falla cerrada de verdad, como en un request sin sesión.
 */
class TenantScopeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        TenantContext::forget();
    }

    protected function tearDown(): void
    {
        TenantContext::forget();
        parent::tearDown();
    }

    /** Un grupo con usuario, indoor, semilla, planta y acción. */
    private function grupo(string $nombre): array
    {
        $tenant = Tenant::factory()->create(['name' => 'GRUPO-'.$nombre]);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        $indoor = Indoor::create([
            'name' => 'INDOOR-'.$nombre, 'large' => 2, 'width' => 2, 'height' => 2,
            'tenant_id' => $tenant->id, 'fans' => ['f1'], 'lamps' => ['l1'],
        ]);

        $seed = Seed::create([
            'name' => 'SEMILLA-'.$nombre, 'tenant_id' => $tenant->id, 'seed_type' => 'auto',
            'flowering_time' => 60, 'ratio_thc' => 20, 'ratio_cbd' => 1,
        ]);

        $plant = Plant::create([
            'name' => 'PLANTA-'.$nombre, 'seed_id' => $seed->id, 'indoor_id' => $indoor->id,
            'state' => 'Vegetativa', 'flowerpot' => '11L', 'capacity' => 11,
        ]);

        $tipo = ActionType::create([
            'name' => 'RIEGO-'.$nombre,
            'tenant_id' => $tenant->id,
            'action_class' => 'App\\Utilities\\PlantActions\\RegisterState',
        ]);

        $accion = Action::create([
            'action_date' => now()->toDateString(), 'indoor_id' => $indoor->id,
            'action_type_id' => $tipo->id, 'tenant_id' => $tenant->id,
            'data' => ['irrigation' => ['irrigation_type' => 'liters', 'liters' => 3]],
        ]);
        $accion->plants()->attach($plant->id);

        return compact('tenant', 'user', 'indoor', 'seed', 'plant', 'tipo', 'accion');
    }

    public function test_sin_contexto_ni_usuario_no_devuelve_nada(): void
    {
        $this->grupo('A');
        $this->grupo('B');

        $this->assertSame(0, Indoor::count(), 'Indoor devolvió filas sin contexto ni sesión');
        $this->assertSame(0, Plant::count(), 'Plant devolvió filas sin contexto ni sesión');
        $this->assertSame(0, Action::count(), 'Action devolvió filas sin contexto ni sesión');
        $this->assertSame(0, Seed::count(), 'Seed devolvió filas sin contexto ni sesión');

        // Los datos están: el que los esconde es el scope.
        $this->assertSame(2, Indoor::withoutGlobalScope(TenantScope::class)->count());
        $this->assertSame(2, Plant::withoutGlobalScope(TenantScope::class)->count());
        $this->assertSame(2, Action::withoutGlobalScope(TenantScope::class)->count());
    }

    public function test_con_contexto_explicito_solo_ve_su_grupo(): void
    {
        $a = $this->grupo('A');
        $this->grupo('B');

        TenantContext::use($a['tenant']->id);

        $this->assertSame(1, Indoor::count());
        $this->assertSame('INDOOR-A', Indoor::first()->name);
        $this->assertSame(1, Plant::count(), 'Plant (scope byIndoor) no filtró por el tenant del indoor');
        $this->assertSame('PLANTA-A', Plant::first()->name);
        $this->assertSame(1, Action::count());
        $this->assertSame($a['accion']->id, Action::first()->id);
        $this->assertSame(1, Seed::count());
    }

    public function test_los_catalogos_ven_lo_propio_y_lo_global_y_nada_ajeno(): void
    {
        $a = $this->grupo('A');
        $this->grupo('B');

        ActionType::create([
            'name' => 'TIPO-GLOBAL', 'tenant_id' => null,
            'action_class' => 'App\\Utilities\\PlantActions\\RegisterState',
        ]);

        TenantContext::use($a['tenant']->id);

        $nombres = ActionType::pluck('name')->all();
        $this->assertContains('RIEGO-A', $nombres, 'el grupo no ve su propio tipo de acción');
        $this->assertContains('TIPO-GLOBAL', $nombres, 'el grupo no ve el catálogo global');
        $this->assertNotContains('RIEGO-B', $nombres, 'el catálogo mostró los tipos personalizados de otro grupo');

        $planes = \App\Models\CropPlan::pluck('name')->all();
        $this->assertSame([], $planes, 'sin planes propios, el grupo no debería ver ninguno');
    }

    public function test_un_usuario_de_un_grupo_solo_ve_lo_suyo(): void
    {
        $a = $this->grupo('A');
        $this->grupo('B');

        $this->actingAs($a['user']);

        $this->assertSame(1, Plant::count());
        $this->assertSame('PLANTA-A', Plant::first()->name);
        $this->assertSame(1, Action::count());
        $this->assertSame('INDOOR-A', Indoor::first()->name);
    }

    public function test_el_superadmin_ve_todo(): void
    {
        $this->grupo('A');
        $this->grupo('B');

        $this->actingAs(User::factory()->create(['tenant_id' => null]));

        $this->assertSame(2, Indoor::count(), 'el superadmin quedó ciego (antes veía 0)');
        $this->assertSame(2, Plant::count());
        $this->assertSame(2, Action::count());
    }

    public function test_el_contexto_explicito_gana_sobre_el_usuario_logueado(): void
    {
        $a = $this->grupo('A');
        $b = $this->grupo('B');

        $this->actingAs($b['user']);
        TenantContext::use($a['tenant']->id);

        $this->assertSame('PLANTA-A', Plant::first()->name);
        $this->assertSame(1, Plant::count());
    }

    public function test_el_contexto_de_servicio_no_filtra(): void
    {
        $this->grupo('A');
        $this->grupo('B');

        TenantContext::useAll();

        $this->assertSame(2, Plant::count());
        $this->assertSame(2, Action::count());
        $this->assertSame(2, Indoor::count());
    }

    public function test_olvidar_el_contexto_vuelve_a_fallar_cerrado(): void
    {
        $a = $this->grupo('A');

        TenantContext::use($a['tenant']->id);
        $this->assertSame(1, Plant::count());

        TenantContext::forget();
        $this->assertSame(0, Plant::count());
    }
}
