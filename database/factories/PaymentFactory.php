<?php

namespace Database\Factories;

use App\Domain\Payments\Enums\PaymentType;
use App\Models\Account;
use App\Models\Business;
use App\Models\Contact;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $business = Business::factory()->create();

        return [
            'business_id' => $business->id,
            'contact_id' => Contact::factory()->create(['business_id' => $business->id])->id,
            'type' => PaymentType::RECEIPT,
            'number' => 'REC-'.fake()->unique()->numberBetween(1000, 9999),
            'date' => fake()->date(),
            'amount' => fake()->randomFloat(2, 100, 10000),
            'bank_account_id' => Account::factory()->create([
                'business_id' => $business->id,
                'type' => 'asset',
                'code' => fake()->unique()->numerify('##00'),
            ])->id,
        ];
    }
}
