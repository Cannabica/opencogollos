<?php

namespace Tests\Unit\Admin;

use App\Models\Tenant;
use App\Services\Admin\AdminNotifierService;
use Tests\TestCase;

class AdminNotifierServiceTest extends TestCase
{
    public function test_notify_returns_zero_and_does_not_throw_without_authorized_chats(): void
    {
        config(['telegram.admin_allowed_user_ids' => '']);

        $this->assertSame(0, app(AdminNotifierService::class)->notify('hola'));
    }

    public function test_notify_catches_failures_and_returns_zero_when_bot_is_not_configured(): void
    {
        config([
            'telegram.admin_allowed_user_ids' => '812714520',
            'telegram.bots.admin.token' => null,
        ]);

        $this->assertSame(0, app(AdminNotifierService::class)->notify('hola'));
    }

    public function test_notify_new_tenant_never_throws(): void
    {
        config([
            'telegram.admin_allowed_user_ids' => '',
        ]);

        $tenant = new Tenant(['id' => 7, 'name' => 'Prueba SA', 'email' => 'p@example.com']);

        $this->assertSame(0, AdminNotifierService::notifyNewTenant($tenant));
    }
}
