<?php

namespace Tests\Unit\Admin;

use App\Models\Tenant;
use App\Services\Admin\AdminDigestService;
use App\Services\Admin\PlatformStatusService;
use App\Services\Admin\TenantAdminService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class AdminDigestServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function serviceWithStatus(array $summary): AdminDigestService
    {
        $status = Mockery::mock(PlatformStatusService::class);
        $status->shouldReceive('summary')->andReturn($summary);

        return new AdminDigestService($status, app(TenantAdminService::class));
    }

    public function test_it_reports_pending_tenants_and_failed_jobs(): void
    {
        $tenant = Tenant::factory()->inactive()->create(['name' => 'Pendiente Digest']);

        $service = $this->serviceWithStatus([
            'db' => true,
            'redis' => true,
            'queue_driver' => 'redis',
            'failed_jobs' => 2,
            'pending_tenants' => 1,
        ]);

        $findings = $service->collectFindings();

        $this->assertNotEmpty($findings);
        $this->assertStringContainsString('2 job(s) fallido(s)', implode("\n", $findings));
        $this->assertStringContainsString("#{$tenant->id} Pendiente Digest", implode("\n", $findings));
    }

    public function test_it_reports_db_and_redis_outages(): void
    {
        $service = $this->serviceWithStatus([
            'db' => false,
            'redis' => false,
            'queue_driver' => 'redis',
            'failed_jobs' => 0,
            'pending_tenants' => 0,
        ]);

        $findings = $service->collectFindings();

        $this->assertNotEmpty($findings);
        $text = implode("\n", $findings);
        $this->assertStringContainsString('base de datos no responde', $text);
        $this->assertStringContainsString('Redis no responde', $text);
    }

    public function test_it_returns_empty_when_everything_is_ok(): void
    {
        $service = $this->serviceWithStatus([
            'db' => true,
            'redis' => true,
            'queue_driver' => 'sync',
            'failed_jobs' => 0,
            'pending_tenants' => 0,
        ]);

        $this->assertSame([], $service->collectFindings());
    }
}
