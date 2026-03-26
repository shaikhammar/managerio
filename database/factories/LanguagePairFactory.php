<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\Language;
use App\Models\LanguagePair;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LanguagePair>
 */
class LanguagePairFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $business = Business::factory()->create();

        return [
            'business_id' => $business->id,
            'source_language_id' => Language::factory()->state(['business_id' => $business->id]),
            'target_language_id' => Language::factory()->state(['business_id' => $business->id]),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
