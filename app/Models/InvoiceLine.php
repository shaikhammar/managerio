<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'account_id',
        'description',
        'quantity',
        'unit_price',
        'discount_percent',
        'tax_code_id',
        'tax_amount',
        'line_total',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'unit_price' => 'decimal:2',
            'discount_percent' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'line_total' => 'decimal:2',
            'sort_order' => 'integer',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function taxCode(): BelongsTo
    {
        return $this->belongsTo(TaxCode::class);
    }

    // ── Helpers ────────────────────────────────────────────────────

    public function calculateLineTotal(): float
    {
        $subtotal = round((float) $this->quantity * (float) $this->unit_price, 2);
        $discount = round($subtotal * (float) $this->discount_percent / 100, 2);

        return $subtotal - $discount;
    }

    public function calculateTaxAmount(): float
    {
        if (! $this->tax_code_id) {
            return 0;
        }

        $lineTotal = $this->calculateLineTotal();

        return round($lineTotal * (float) $this->taxCode->rate / 100, 2);
    }
}
