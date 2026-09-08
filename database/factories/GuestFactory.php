<?php

namespace Database\Factories;

use App\Models\Guest;
use App\Models\Invitation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Guest>
 */
class GuestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'invitation_id' => Invitation::factory(),
            'name' => fake()->name(),
            'attending' => fake()->randomElement(['attending', 'not_attending', 'maybe']),
            'party_size' => fake()->numberBetween(1, 4),
            'message' => fake()->optional()->sentence(),
            'responded_at' => now(),
        ];
    }
}
