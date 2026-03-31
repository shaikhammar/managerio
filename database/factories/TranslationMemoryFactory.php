<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\Language;
use App\Models\TranslationMemory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TranslationMemory>
 */
class TranslationMemoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $business = Business::factory()->create();

        return [
            'business_id' => $business->id,
            'contact_id' => null,
            'source_language_id' => Language::factory()->state(['business_id' => $business->id]),
            'target_language_id' => Language::factory()->state(['business_id' => $business->id]),
            'name' => $this->faker->words(3, true).' TM',
            'software' => $this->faker->optional()->randomElement(['SDL Trados', 'memoQ', 'OmegaT', 'Wordfast']),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
