<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\TaxCode;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TaxCode>
 */
class TaxCodeFactory extends Factory
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
            'name' => fake()->word().' Tax',
            'description' => fake()->sentence(),
            'rate' => fake()->randomFloat(2, 0, 25),
            'is_active' => true,
        ];
    }
}
