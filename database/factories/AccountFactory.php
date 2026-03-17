<?php

namespace Database\Factories;

use App\Domain\Accounting\Enums\AccountSubType;
use App\Domain\Accounting\Enums\AccountType;
use App\Models\Account;
use App\Models\Business;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Account>
 */
class AccountFactory extends Factory
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
            'code' => (string) fake()->unique()->numberBetween(1000, 9999),
            'name' => fake()->words(2, true),
            'type' => AccountType::ASSET,
            'sub_type' => AccountSubType::OTHER_CURRENT_ASSET,
            'description' => fake()->sentence(),
            'is_system' => false,
            'is_active' => true,
        ];
    }

    public function type(AccountType $type): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => $type,
        ]);
    }

    public function subType(AccountSubType $subType): static
    {
        return $this->state(fn (array $attributes) => [
            'sub_type' => $subType,
        ]);
    }

    public function system(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_system' => true,
        ]);
    }
}
