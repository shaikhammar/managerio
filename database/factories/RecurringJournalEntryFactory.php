<?php

namespace Database\Factories;

use App\Domain\Accounting\Enums\RecurringFrequency;
use App\Models\Business;
use App\Models\RecurringJournalEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RecurringJournalEntry>
 */
class RecurringJournalEntryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'frequency' => fake()->randomElement(RecurringFrequency::cases())->value,
            'start_date' => now()->startOfMonth(),
            'end_date' => null,
            'next_run_date' => now()->startOfMonth(),
            'last_run_at' => null,
            'day_of_month' => 1,
            'is_active' => true,
            'template_lines' => [],
            'created_by' => User::factory(),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['is_active' => false]);
    }

    public function due(): static
    {
        return $this->state(fn (array $attributes) => ['next_run_date' => now()->subDay()->toDateString()]);
    }
}
