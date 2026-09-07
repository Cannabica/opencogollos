<?php

namespace Tests\Feature\Console;

use App\Models\Tenant;
use App\Models\UsageEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsagePruneCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_prunes_usage_events_older_than_default_retention(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        UsageEvent::factory()->for($user)->create(['created_at' => now()->subDays(120)]);
        UsageEvent::factory()->for($user)->create(['created_at' => now()->subDays(91)]);
        UsageEvent::factory()->for($user)->create(['created_at' => now()->subDays(89)]);
        UsageEvent::factory()->for($user)->create(['created_at' => now()]);

        $this->artisan('usage:prune')->assertSuccessful();

        $this->assertDatabaseCount('usage_events', 2);
    }

    public function test_prune_respects_custom_days_option(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        UsageEvent::factory()->for($user)->create(['created_at' => now()->subDays(30)]);
        UsageEvent::factory()->for($user)->create(['created_at' => now()->subDays(10)]);

        $this->artisan('usage:prune', ['--days' => 15])->assertSuccessful();

        $this->assertDatabaseCount('usage_events', 1);
    }
}
