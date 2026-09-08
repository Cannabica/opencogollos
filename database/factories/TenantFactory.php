<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tenant>
 */
class TenantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'email' => $this->faker->unique()->safeEmail(),
            'active' => true,
            'user_type' => 'individual',
            'usage_type' => 'personal',
            'team_emails' => null,
            'plants_per_cycle' => 1,
            'harvest_products' => json_encode(['flores']),
            'activated_at' => now(),
        ];
    }

    /**
     * Indicate that the tenant is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => false,
        ]);
    }

    /**
     * Indicate that the tenant is not activated.
     */
    public function notActivated(): static
    {
        return $this->state(fn (array $attributes) => [
            'activated_at' => null,
        ]);
    }
}