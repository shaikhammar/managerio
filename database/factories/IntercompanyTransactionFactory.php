<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Business;
use App\Models\IntercompanyTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IntercompanyTransaction>
 */
class IntercompanyTransactionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'source_business_id' => Business::factory(),
            'target_business_id' => Business::factory(),
            'source_account_id' => Account::factory(),
            'target_account_id' => Account::factory(),
            'amount' => fake()->randomFloat(2, 100, 10000),
            'date' => fake()->dateThisYear()->format('Y-m-d'),
            'description' => fake()->sentence(),
            'reference' => null,
            'source_journal_entry_id' => null,
            'target_journal_entry_id' => null,
            'created_by' => User::factory(),
        ];
    }
}
