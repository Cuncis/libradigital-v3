<?php

namespace Database\Factories;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Plan>
 */
class PlanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $tier = fake()->randomElement(['starter', 'plus', 'pro', 'organizer']);
        $interval = fake()->randomElement(['monthly', 'yearly']);

        return [
            'name' => ucfirst($tier).' '.ucfirst($interval),
            'tier' => $tier,
            'billing_interval' => $interval,
            'price' => fake()->numberBetween(49_000, 499_000),
            'currency' => 'IDR',
            'invitation_limit' => fake()->numberBetween(1, 10),
            'features' => [
                'custom_domain' => false,
                'remove_branding' => false,
                'guest_personalization' => true,
            ],
        ];
    }

    public function monthly(): static
    {
        return $this->state(['billing_interval' => 'monthly']);
    }

    public function yearly(): static
    {
        return $this->state(['billing_interval' => 'yearly']);
    }
}
