<?php

namespace Database\Factories;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subscription>
 */
class SubscriptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'plan_id' => fn () => Plan::query()->inRandomOrder()->value('id') ?? Plan::factory(),
            'status' => 'pending',
            'current_period_end' => null,
        ];
    }

    public function active(): static
    {
        return $this->state([
            'status' => 'active',
            'current_period_end' => now()->addMonth(),
        ]);
    }
}
