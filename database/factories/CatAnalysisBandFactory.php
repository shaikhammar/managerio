<?php

namespace Database\Factories;

use App\Domain\Translation\Enums\CatMatchBand;
use App\Models\CatAnalysis;
use App\Models\CatAnalysisBand;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CatAnalysisBand>
 */
class CatAnalysisBandFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $band = $this->faker->randomElement(CatMatchBand::cases());

        return [
            'cat_analysis_id' => CatAnalysis::factory(),
            'band' => $band->value,
            'words' => $this->faker->numberBetween(0, 5000),
            'discount_percent' => $band->defaultDiscountPercent(),
        ];
    }
}
