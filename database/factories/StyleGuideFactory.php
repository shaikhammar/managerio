<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\StyleGuide;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StyleGuide>
 */
class StyleGuideFactory extends Factory
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
            'contact_id' => null,
            'name' => $this->faker->words(3, true).' Style Guide',
            'description' => $this->faker->optional()->paragraph(),
            'file_path' => null,
            'file_name' => null,
            'file_size' => null,
        ];
    }
}
