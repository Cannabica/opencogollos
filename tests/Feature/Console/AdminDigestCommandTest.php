<?php

namespace Tests\Feature\Console;

use App\Services\Admin\AdminDigestService;
use Mockery;
use Tests\TestCase;

class AdminDigestCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function bindDigestService(array $findings): void
    {
        $service = Mockery::mock(AdminDigestService::class);
        $service->shouldReceive('collectFindings')->andReturn($findings);

        $this->app->instance(AdminDigestService::class, $service);
    }

    public function test_it_does_nothing_when_digest_is_disabled(): void
    {
        config(['telegram.admin_digest_enabled' => false]);

        $this->artisan('admin:digest')
            ->expectsOutputToContain('deshabilitado')
            ->assertExitCode(0);
    }

    public function test_it_reports_no_news_without_findings(): void
    {
        config(['telegram.admin_digest_enabled' => true]);
        $this->bindDigestService([]);

        $this->artisan('admin:digest')
            ->expectsOutputToContain('Sin novedades')
            ->assertExitCode(0);
    }

    public function test_it_fails_when_there_are_findings_but_no_authorized_chats(): void
    {
        config([
            'telegram.admin_digest_enabled' => true,
            'telegram.admin_allowed_user_ids' => '',
        ]);
        $this->bindDigestService(['❌ Algo falló.']);

        $this->artisan('admin:digest')
            ->expectsOutputToContain('No hay ids de Telegram autorizados')
            ->assertExitCode(1);
    }
}
