<?php

namespace Database\Factories;

use App\Domain\Translation\Enums\TranslatorAvailability;
use App\Models\Business;
use App\Models\Contact;
use App\Models\TranslatorProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TranslatorProfile>
 */
class TranslatorProfileFactory extends Factory
{
    public function definition(): array
    {
        $business = Business::factory()->create();

        return [
            'business_id' => $business->id,
            'contact_id' => Contact::factory()->supplier()->create(['business_id' => $business->id])->id,
            'availability' => TranslatorAvailability::Available->value,
            'quality_rating' => null,
            'quality_notes' => null,
            'specialisations' => [],
            'cat_tools' => [],
            'certifications' => [],
        ];
    }

    public function available(): static
    {
        return $this->state(fn (array $attributes) => [
            'availability' => TranslatorAvailability::Available->value,
        ]);
    }

    public function busy(): static
    {
        return $this->state(fn (array $attributes) => [
            'availability' => TranslatorAvailability::Busy->value,
        ]);
    }
}
