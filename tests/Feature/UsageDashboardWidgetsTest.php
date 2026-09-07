<?php

namespace Tests\Feature;

use App\Filament\Widgets\ActivationFunnelWidget;
use App\Filament\Widgets\TopModulesWidget;
use App\Filament\Widgets\UsageActivityHeatmapWidget;
use App\Filament\Widgets\UsageStatsOverview;
use App\Models\Indoor;
use App\Models\Tenant;
use App\Models\UsageEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UsageDashboardWidgetsTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_dashboard_returns_ok(): void
    {
        $superadmin = User::factory()->create();
        Tenant::factory()->count(2)->create();

        $this->actingAs($superadmin)->get('/superadmin')->assertOk();
    }

    public function test_tenant_user_cannot_access_superadmin_dashboard(): void
    {
        $tenant = Tenant::factory()->create();
        $tenantUser = User::factory()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($tenantUser)->get('/superadmin')->assertForbidden();
    }

    public function test_stats_widget_renders_usage_counts(): void
    {
        $tenant = Tenant::factory()->create();
        $tenantUser = User::factory()->create(['tenant_id' => $tenant->id]);

        UsageEvent::factory()->for($tenantUser)->create([
            'tenant_id' => $tenant->id,
            'created_at' => now()->subHours(2),
        ]);
        UsageEvent::factory()->for($tenantUser)->create([
            'tenant_id' => $tenant->id,
            'created_at' => now()->subHour(),
        ]);

        Livewire::test(UsageStatsOverview::class)
            ->assertSee('Páginas vistas (7d)', false)
            ->assertSee('Tenants activos (7d)', false);
    }

    public function test_activation_funnel_widget_shows_steps(): void
    {
        $tenant = Tenant::factory()->create();
        Indoor::create([
            'name' => 'Indoor 1',
            'tenant_id' => $tenant->id,
            'large' => 100,
            'width' => 100,
            'height' => 200,
        ]);

        Livewire::test(ActivationFunnelWidget::class)
            ->assertSee('Embudo de activación', false)
            ->assertSee('Tenants registrados', false)
            ->assertSee('Crearon un indoor', false);
    }

    public function test_top_modules_widget_shows_empty_state_without_data(): void
    {
        Livewire::test(TopModulesWidget::class)
            ->assertSee('Todavía no hay datos de telemetría', false);
    }

    public function test_top_modules_widget_lists_used_modules(): void
    {
        $tenant = Tenant::factory()->create();
        $tenantUser = User::factory()->create(['tenant_id' => $tenant->id]);

        UsageEvent::factory()->for($tenantUser)->create([
            'tenant_id' => $tenant->id,
            'route_name' => 'filament.tenant.resources.plants.index',
            'module' => 'plants',
            'action' => 'index',
            'created_at' => now()->subDay(),
        ]);

        Livewire::test(TopModulesWidget::class)
            ->assertSee('Módulos más usados', false)
            ->assertSee('Plantas', false)
            ->assertDontSee('Todavía no hay datos de telemetría', false);
    }

    public function test_heatmap_widget_renders_without_crashing(): void
    {
        $tenant = Tenant::factory()->create();
        $tenantUser = User::factory()->create(['tenant_id' => $tenant->id]);
        UsageEvent::factory()->for($tenantUser)->create([
            'tenant_id' => $tenant->id,
            'created_at' => now()->subHour(),
        ]);

        Livewire::test(UsageActivityHeatmapWidget::class)
            ->assertSee('Actividad de la plataforma', false);
    }
}
