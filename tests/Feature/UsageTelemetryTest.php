<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\UsageEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsageTelemetryTest extends TestCase
{
    use RefreshDatabase;

    public function test_logs_tenant_dashboard_page_view(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($user)->get('/tenant')->assertOk();

        $this->assertDatabaseHas('usage_events', [
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'panel' => 'tenant',
            'route_name' => 'filament.tenant.pages.dashboard',
            'module' => 'dashboard',
            'action' => null,
        ]);
    }

    public function test_logs_tenant_resource_index_with_module_and_action(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($user)->get('/tenant/plants')->assertOk();

        $this->assertDatabaseHas('usage_events', [
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'panel' => 'tenant',
            'route_name' => 'filament.tenant.resources.plants.index',
            'module' => 'plants',
            'action' => 'index',
        ]);
    }

    public function test_logs_superadmin_dashboard_page_view_without_tenant(): void
    {
        $user = User::factory()->create(); // tenant_id null => superadmin

        $this->actingAs($user)->get('/superadmin')->assertOk();

        $this->assertDatabaseHas('usage_events', [
            'tenant_id' => null,
            'user_id' => $user->id,
            'panel' => 'superadmin',
            'route_name' => 'filament.superadmin.pages.dashboard',
            'module' => 'dashboard',
            'action' => null,
        ]);
    }

    public function test_does_not_log_guest_requests_on_auth_pages(): void
    {
        $this->get('/tenant/login')->assertOk();
        $this->get('/superadmin/login')->assertOk();

        $this->assertDatabaseCount('usage_events', 0);
    }

    public function test_does_not_log_redirects_when_tenant_is_not_active(): void
    {
        $tenant = Tenant::factory()->inactive()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($user)->get('/tenant/plants')->assertRedirect('/tenant/activation-pending');

        $this->assertDatabaseCount('usage_events', 0);
    }
}
