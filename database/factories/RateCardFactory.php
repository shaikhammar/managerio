<?php

namespace Database\Factories;

use App\Domain\Translation\Enums\BillingUnit;
use App\Domain\Translation\Enums\RateCardType;
use App\Models\Business;
use App\Models\LanguagePair;
use App\Models\RateCard;
use App\Models\ServiceType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RateCard>
 */
class RateCardFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $business = Business::factory()->create();

        return [
            'business_id' => $business->id,
            'type' => RateCardType::Default->value,
            'contact_id' => null,
            'language_pair_id' => LanguagePair::factory()->state(['business_id' => $business->id]),
            'service_type_id' => ServiceType::factory()->state(['business_id' => $business->id]),
            'unit_rate' => $this->faker->randomFloat(4, 0.05, 0.30),
            'unit' => BillingUnit::Word->value,
            'minimum_fee' => null,
            'rush_multiplier' => null,
            'rush_fixed_surcharge' => null,
            'notes' => null,
            'is_active' => true,
        ];
    }

    public function client(): static
    {
        return $this->state(['type' => RateCardType::Client->value]);
    }

    public function translator(): static
    {
        return $this->state(['type' => RateCardType::Translator->value]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
