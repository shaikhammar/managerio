<?php

namespace Database\Factories;

use App\Domain\Translation\Enums\BillingUnit;
use App\Models\Business;
use App\Models\ServiceType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceType>
 */
class ServiceTypeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->randomElement([
            'Translation', 'Editing', 'Proofreading', 'DTP', 'Localization',
            'Transcription', 'Interpreting', 'Subtitling', 'Post-editing (MTPE)',
        ]);

        return [
            'business_id' => Business::factory(),
            'name' => $name,
            'code' => strtolower(str_replace([' ', '(', ')'], ['_', '', ''], $name)),
            'description' => fake()->optional()->sentence(),
            'default_unit' => BillingUnit::Word,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
