<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\AccountBudget;
use App\Models\Business;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AccountBudget>
 */
class AccountBudgetFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'account_id' => Account::factory(),
            'year' => now()->year,
            'month' => fake()->numberBetween(1, 12),
            'amount' => fake()->randomFloat(2, 100, 10000),
        ];
    }
}
