<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\Language;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Language>
 */
class LanguageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        static $codes = ['en', 'fr', 'de', 'es', 'it', 'pt', 'nl', 'pl', 'ru', 'ar', 'zh', 'ja', 'ko', 'tr', 'sv'];
        static $used = [];

        do {
            $code = fake()->randomElement($codes).fake()->numerify('##');
        } while (in_array($code, $used));

        $used[] = $code;

        return [
            'business_id' => Business::factory(),
            'code' => $code,
            'name' => fake()->unique()->word(),
            'native_name' => fake()->optional()->word(),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
