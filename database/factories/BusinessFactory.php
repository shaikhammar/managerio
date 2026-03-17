<?php

namespace Database\Factories;

use App\Models\Business;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Business>
 */
class BusinessFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'legal_name' => fn (array $attributes) => $attributes['name'].' Inc.',
            'tax_number' => fake()->numerify('##-#######'),
            'email' => fake()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'address_line_1' => fake()->streetAddress(),
            'city' => fake()->city(),
            'state' => fake()->stateAbbr(),
            'postal_code' => fake()->postcode(),
            'country' => 'US',
            'currency_code' => 'USD',
            'fiscal_year_start' => 1,
        ];
    }
}
