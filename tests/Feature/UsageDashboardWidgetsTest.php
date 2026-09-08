<?php

namespace Tests\Feature;

use App\Models\Action;
use App\Models\ActionType;
use App\Models\Indoor;
use App\Models\Plant;
use App\Models\Seed;
use App\Models\Tenant;
use App\Models\UsageEvent;
use App\Models\User;
use App\Support\UsageStats;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsageDashboardWidgetsTest extends TestCase
{
    use RefreshDatabase;

    public function test_usage_analytics_page_renders_summary_and_funnel(): void
    {
        $superadmin = User::factory()->create();

        $tenant = Tenant::factory()->create();
        $tenantUser = User::factory()->create(['tenant_id' => $tenant->id]);
        $indoor = Indoor::create([
            'name' => 'Indoor 1',
            'tenant_id' => $tenant->id,
            'large' => 100,
            'width' => 100,
            'height' => 200,
        ]);
        $actionType = ActionType::create(['name' => 'Riego', 'action_class' => 'irrigation']);
        Action::create([
            'action_type_id' => $actionType->id,
            'indoor_id' => $indoor->id,
            'tenant_id' => $tenant->id,
            'action_date' => now()->subHour(),
        ]);
        UsageEvent::factory()->for($tenantUser)->create([
            'tenant_id' => $tenant->id,
            'route_name' => 'filament.tenant.resources.plants.index',
            'module' => 'plants',
            'action' => 'index',
            'created_at' => now()->subHour(),
        ]);

        $this->actingAs($superadmin)
            ->get('/superadmin/usage-analytics')
            ->assertOk()
            ->assertSee('Embudo de activación', false)
            ->assertSee('Crearon un indoor', false)
            ->assertSee('Módulos más usados', false)
            ->assertSee('Páginas vistas (7d)', false)
            ->assertSee('Actividad de la plataforma', false)
            ->assertSee('Plantas', false);
    }

    public function test_tenant_user_cannot_access_usage_analytics_page(): void
    {
        $tenant = Tenant::factory()->create();
        $tenantUser = User::factory()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($tenantUser)->get('/superadmin/usage-analytics')->assertForbidden();
    }

    public function test_funnel_counts_tenants_that_created_indoors(): void
    {
        $tenant = Tenant::factory()->create();
        Tenant::factory()->count(2)->create();

        Indoor::create([
            'name' => 'Indoor 1',
            'tenant_id' => $tenant->id,
            'large' => 100,
            'width' => 100,
            'height' => 200,
        ]);

        $funnel = collect(UsageStats::funnel())->keyBy('label');

        $this->assertSame(3, $funnel['Tenants registrados']['count']);
        $this->assertSame(1, $funnel['Crearon un indoor']['count']);
        $this->assertSame(100, $funnel['Tenants registrados']['pct']);
    }

    public function test_funnel_ignores_tenant_scope_for_authenticated_superadmin(): void
    {
        // Reproduce el bug real: con un superadmin autenticado (tenant_id null),
        // el TenantScope de Indoor filtraba a tenant_id NULL y el embudo daba 0.
        $superadmin = User::factory()->create();
        $this->actingAs($superadmin);

        $tenant = Tenant::factory()->create();
        Tenant::factory()->count(2)->create();

        Indoor::create([
            'name' => 'Indoor 1',
            'tenant_id' => $tenant->id,
            'large' => 100,
            'width' => 100,
            'height' => 200,
        ]);

        $funnel = collect(UsageStats::funnel())->keyBy('label');

        $this->assertSame(1, $funnel['Crearon un indoor']['count']);
    }

    public function test_top_modules_empty_state_without_data(): void
    {
        $superadmin = User::factory()->create();
        Tenant::factory()->count(2)->create();

        $this->actingAs($superadmin)
            ->get('/superadmin/usage-analytics')
            ->assertOk()
            ->assertSee('Todavía no hay datos de telemetría', false);
    }

    public function test_activity_matrix_shape(): void
    {
        $tenant = Tenant::factory()->create();
        $tenantUser = User::factory()->create(['tenant_id' => $tenant->id]);
        UsageEvent::factory()->for($tenantUser)->create([
            'tenant_id' => $tenant->id,
            'created_at' => now(),
        ]);

        $matrix = UsageStats::activityMatrix();

        $this->assertCount(7, $matrix['labels']);
        $this->assertCount(24, $matrix['data']);
        $this->assertSame(1, array_sum($matrix['data'][now()->hour]));
    }

    public function test_stuck_by_step_lists_tenants_in_each_gap(): void
    {
        $this->actingAs(User::factory()->create()); // superadmin: aplica TenantScope, el fix debe ignorarlo

        $withNothing = Tenant::factory()->create();
        $withIndoor = Tenant::factory()->create();
        $indoor = Indoor::create([
            'name' => 'Indoor 1',
            'tenant_id' => $withIndoor->id,
            'large' => 100,
            'width' => 100,
            'height' => 200,
        ]);

        $seed = Seed::create([
            'name' => 'Genética',
            'seed_type' => 'fotoperiodica',
            'flowering_time' => 60,
            'ratio_thc' => 10,
            'ratio_cbd' => 1,
        ]);
        Plant::create([
            'name' => 'Planta 1',
            'seed_id' => $seed->id,
            'indoor_id' => $indoor->id,
            'state' => 'vegetativo',
            'flowerpot' => 'Maceta 10L',
            'capacity' => 10,
        ]);

        $stuck = UsageStats::stuckByStep();

        $this->assertTrue($stuck['sin_indoor']->contains('id', $withNothing->id));
        $this->assertFalse($stuck['sin_indoor']->contains('id', $withIndoor->id));
        $this->assertTrue($stuck['con_plantas_sin_acciones']->contains('id', $withIndoor->id));
        $this->assertFalse($stuck['con_indoor_sin_plantas']->contains('id', $withIndoor->id));
    }

    public function test_funnel_by_segment_groups_by_user_type(): void
    {
        $this->actingAs(User::factory()->create());

        $individual = Tenant::factory()->create(['user_type' => 'individual']);
        Tenant::factory()->create(['user_type' => 'individual']);
        Tenant::factory()->create(['user_type' => 'cooperativa']);

        $indoor = Indoor::create([
            'name' => 'Indoor 1',
            'tenant_id' => $individual->id,
            'large' => 100,
            'width' => 100,
            'height' => 200,
        ]);

        $rows = collect(UsageStats::funnelBySegment('user_type'))->keyBy('label');

        $this->assertSame(2, $rows['Individual']['registered']);
        $this->assertSame(1, $rows['Individual']['with_indoor']);
        $this->assertSame(1, $rows['Cooperativa']['registered']);
        $this->assertSame(0, $rows['Cooperativa']['with_indoor']);
    }

    public function test_usage_analytics_page_shows_stuck_and_segments_sections(): void
    {
        $superadmin = User::factory()->create();
        $tenant = Tenant::factory()->create(['user_type' => 'individual']);
        $stuckTenant = Tenant::factory()->create(['user_type' => 'cooperativa']);

        $this->actingAs($superadmin)
            ->get('/superadmin/usage-analytics')
            ->assertOk()
            ->assertSee('Tenants estancados por paso', false)
            ->assertSee('Conversión por perfil', false)
            ->assertSee($stuckTenant->email, false)
            ->assertSee('Tipo de usuario', false)
            ->assertSee('Cooperativa', false);
    }
}
