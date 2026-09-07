<?php

namespace Tests\Unit\Admin;

use App\Models\Tenant;
use App\Models\User;
use App\Services\Admin\TenantAdminService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantAdminServiceTest extends TestCase
{
    use RefreshDatabase;

    private TenantAdminService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(TenantAdminService::class);
    }

    public function test_it_lists_tenants_with_user_count(): void
    {
        $tenant = Tenant::factory()->create(['name' => 'Cultivo Norte']);
        User::factory()->count(2)->create(['tenant_id' => $tenant->id]);

        $list = $this->service->list();

        $this->assertCount(1, $list);
        $this->assertSame($tenant->id, $list[0]['id']);
        $this->assertSame('Cultivo Norte', $list[0]['name']);
        $this->assertSame(2, $list[0]['users']);
        $this->assertTrue($list[0]['active']);
    }

    public function test_it_returns_pending_tenants_only(): void
    {
        Tenant::factory()->create();
        $pending = Tenant::factory()->inactive()->create(['name' => 'Pendiente SA']);

        $result = $this->service->pendingActivation();

        $this->assertCount(1, $result);
        $this->assertSame('Pendiente SA', $result[0]['name']);
    }

    public function test_it_returns_null_for_a_non_existent_tenant(): void
    {
        $this->assertNull($this->service->detail(9999));
    }

    public function test_it_builds_the_detail_of_a_tenant(): void
    {
        $tenant = Tenant::factory()->create([
            'name' => 'Detalle SA',
            'email' => 'detalle@example.com',
        ]);
        User::factory()->count(2)->create(['tenant_id' => $tenant->id]);

        $detail = $this->service->detail($tenant->id);

        $this->assertNotNull($detail);
        $this->assertSame('Detalle SA', $detail['name']);
        $this->assertSame('detalle@example.com', $detail['email']);
        $this->assertTrue($detail['active']);
        $this->assertNull($detail['owner']);
        $this->assertSame(2, $detail['web_users']);
        $this->assertSame(0, $detail['telegram_users']);
        $this->assertSame(0, $detail['indoors']);
        $this->assertSame(0, $detail['plants']);
        $this->assertSame(0, $detail['seeds']);
        $this->assertSame(0, $detail['crop_plans']);
    }

    public function test_it_counts_global_metrics(): void
    {
        Tenant::factory()->count(2)->create();
        Tenant::factory()->inactive()->create();

        $metrics = $this->service->globalMetrics();

        $this->assertSame(3, $metrics['tenants']);
        $this->assertSame(2, $metrics['tenants_active']);
        $this->assertSame(0, $metrics['users']);
        $this->assertSame(0, $metrics['plants']);
        $this->assertSame(0, $metrics['actions']);
        $this->assertSame(0, $metrics['seeds']);
    }

    public function test_it_activates_a_tenant(): void
    {
        $tenant = Tenant::factory()->inactive()->create();

        $result = $this->service->setActive($tenant->id, true);

        $this->assertNotNull($result);
        $this->assertTrue($result['active']);
        $this->assertTrue((bool) Tenant::find($tenant->id)->active);
        $this->assertNotNull(Tenant::find($tenant->id)->activated_at);
    }

    public function test_it_deactivates_a_tenant(): void
    {
        $tenant = Tenant::factory()->create();

        $result = $this->service->setActive($tenant->id, false);

        $this->assertNotNull($result);
        $this->assertFalse($result['active']);
        $this->assertFalse((bool) Tenant::find($tenant->id)->active);
    }

    public function test_set_active_returns_null_for_a_missing_tenant(): void
    {
        $this->assertNull($this->service->setActive(9999, true));
    }
}
