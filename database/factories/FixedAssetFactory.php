<?php

namespace Database\Factories;

use App\Domain\Accounting\Enums\AssetStatus;
use App\Domain\Accounting\Enums\DepreciationMethod;
use App\Models\Account;
use App\Models\Business;
use App\Models\FixedAsset;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FixedAsset>
 */
class FixedAssetFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'asset_account_id' => Account::factory(),
            'accumulated_depreciation_account_id' => Account::factory(),
            'depreciation_expense_account_id' => Account::factory(),
            'name' => fake()->words(3, true),
            'description' => fake()->optional()->sentence(),
            'asset_tag' => 'FA-'.fake()->numerify('####'),
            'purchase_date' => fake()->dateTimeBetween('-3 years', '-1 month')->format('Y-m-d'),
            'purchase_cost' => fake()->randomFloat(2, 1000, 50000),
            'salvage_value' => 0,
            'useful_life_months' => fake()->randomElement([36, 48, 60, 84, 120]),
            'depreciation_method' => DepreciationMethod::StraightLine->value,
            'status' => AssetStatus::Active->value,
            'disposal_date' => null,
            'disposal_proceeds' => null,
            'notes' => null,
            'created_by' => User::factory(),
        ];
    }

    public function retired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AssetStatus::Retired->value,
        ]);
    }

    public function disposed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AssetStatus::Disposed->value,
            'disposal_date' => now()->toDateString(),
            'disposal_proceeds' => 0,
        ]);
    }

    public function decliningBalance(): static
    {
        return $this->state(fn (array $attributes) => [
            'depreciation_method' => DepreciationMethod::DecliningBalance->value,
        ]);
    }
}
