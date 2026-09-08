<?php

namespace Database\Factories;

use App\Models\CustomRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomRequest>
 */
class CustomRequestFactory extends Factory
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
            'event_type' => fake()->randomElement(['wedding', 'birthday', 'corporate']),
            'event_date' => fake()->dateTimeBetween('+1 week', '+6 months'),
            'style_notes' => fake()->paragraph(),
            'budget' => fake()->numberBetween(300_000, 3_000_000),
            'status' => 'new',
        ];
    }
}
