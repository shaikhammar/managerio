<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\Contact;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contact>
 */
class ContactFactory extends Factory
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
            'type' => 'customer',
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'is_active' => true,
        ];
    }

    public function supplier(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'supplier',
        ]);
    }
}
