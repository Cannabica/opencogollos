<?php

namespace Tests\Unit\Telegram;

use App\Services\Admin\AdminAuthorizer;
use Tests\TestCase;

class AdminAuthorizerTest extends TestCase
{
    private AdminAuthorizer $authorizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->authorizer = app(AdminAuthorizer::class);
    }

    public function test_allows_a_user_id_listed_in_the_allowlist(): void
    {
        config(['telegram.admin_allowed_user_ids' => '812714520, 111222333']);

        $this->assertTrue($this->authorizer->isAllowed(812714520));
        $this->assertTrue($this->authorizer->isAllowed(111222333));
    }

    public function test_denies_user_ids_not_listed_in_the_allowlist(): void
    {
        config(['telegram.admin_allowed_user_ids' => '812714520']);

        $this->assertFalse($this->authorizer->isAllowed(999999));
        $this->assertFalse($this->authorizer->isAllowed(null));
    }

    public function test_denies_everyone_when_the_allowlist_is_empty(): void
    {
        config(['telegram.admin_allowed_user_ids' => '']);

        $this->assertFalse($this->authorizer->isAllowed(812714520));
        $this->assertSame([], $this->authorizer->allowedUserIds());
    }

    public function test_ignores_non_numeric_entries_in_the_allowlist(): void
    {
        config(['telegram.admin_allowed_user_ids' => 'abc,812714520,12.5, -42']);

        $this->assertSame([812714520], $this->authorizer->allowedUserIds());
    }
}
