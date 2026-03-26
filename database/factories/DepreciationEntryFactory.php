<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\DepreciationEntry;
use App\Models\FixedAsset;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DepreciationEntry>
 */
class DepreciationEntryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $periodStart = fake()->dateTimeBetween('-2 years', 'now')->format('Y-m-01');

        return [
            'business_id' => Business::factory(),
            'fixed_asset_id' => FixedAsset::factory(),
            'journal_entry_id' => null,
            'period_start' => $periodStart,
            'period_end' => date('Y-m-t', strtotime($periodStart)),
            'depreciation_amount' => fake()->randomFloat(2, 50, 500),
            'created_by' => User::factory(),
        ];
    }
}
