<?php

namespace Database\Factories;

use App\Domain\Sales\Enums\InvoiceStatus;
use App\Domain\Sales\Enums\InvoiceType;
use App\Models\Business;
use App\Models\Contact;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $business = Business::factory();

        return [
            'business_id' => $business,
            'contact_id' => Contact::factory(),
            'type' => InvoiceType::INVOICE,
            'number' => 'INV-'.fake()->unique()->numberBetween(1000, 9999),
            'date' => now(),
            'due_date' => now()->addDays(30),
            'status' => InvoiceStatus::DRAFT,
            'subtotal' => 0,
            'tax_amount' => 0,
            'total' => 0,
            'amount_paid' => 0,
            'balance_due' => 0,
            'currency_code' => 'USD',
            'exchange_rate' => 1.0,
        ];
    }

    public function type(InvoiceType $type): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => $type,
        ]);
    }

    public function status(InvoiceStatus $status): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => $status,
        ]);
    }
}
