<?php

namespace Tests\Unit\Admin;

use App\Services\Admin\PlatformStatusService;
use Tests\TestCase;

class PlatformStatusServiceTest extends TestCase
{
    public function test_redis_check_is_skipped_when_redis_is_not_used_by_the_stack(): void
    {
        config([
            'cache.default' => 'file',
            'queue.default' => 'sync',
            'session.driver' => 'file',
        ]);

        $this->assertNull(app(PlatformStatusService::class)->checkRedis());
    }
}
