<?php

namespace Database\Factories;

use App\Models\UsageEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UsageEvent>
 */
class UsageEventFactory extends Factory
{
    protected $model = UsageEvent::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'tenant_id' => null,
            'panel' => 'tenant',
            'route_name' => 'filament.tenant.pages.dashboard',
            'module' => 'dashboard',
            'action' => null,
            'created_at' => now(),
        ];
    }
}
