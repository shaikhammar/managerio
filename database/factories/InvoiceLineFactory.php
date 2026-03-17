<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvoiceLine>
 */
class InvoiceLineFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'invoice_id' => Invoice::factory(),
            'account_id' => Account::factory(),
            'description' => fake()->sentence(),
            'quantity' => 1,
            'unit_price' => fake()->randomFloat(2, 10, 1000),
            'discount_percent' => 0,
            'tax_code_id' => null,
            'tax_amount' => 0,
            'line_total' => 0,
            'sort_order' => 0,
        ];
    }
}
