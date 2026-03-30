<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\TermBase;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TermBase>
 */
class TermBaseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subjectFields = ['General', 'Legal', 'Medical', 'Technical', 'Marketing', 'Financial', 'IT', 'Life Sciences'];

        return [
            'business_id' => Business::factory(),
            'contact_id' => null,
            'name' => $this->faker->words(3, true).' TB',
            'subject_field' => $this->faker->optional()->randomElement($subjectFields),
            'description' => $this->faker->optional()->sentence(),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
