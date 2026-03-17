<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\JournalEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JournalEntry>
 */
class JournalEntryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'entry_number' => 'JE-'.fake()->unique()->numberBetween(1000, 9999),
            'date' => now(),
            'description' => fake()->sentence(),
            'is_posted' => true,
            'posted_at' => now(),
            'created_by' => User::factory(),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_posted' => false,
            'posted_at' => null,
        ]);
    }
}
