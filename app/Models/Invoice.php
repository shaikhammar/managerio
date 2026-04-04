<?php

namespace App\Models;

use App\Domain\Sales\Enums\InvoiceStatus;
use App\Domain\Sales\Enums\InvoiceType;
use App\Domain\Shared\Concerns\Auditable;
use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use Auditable, BelongsToBusiness, HasFactory;

    /** @var list<string> */
    protected array $auditExclude = ['created_at', 'updated_at', 'amount_paid', 'balance_due'];

    protected string $auditLabel = 'number';

    protected $fillable = [
        'business_id',
        'contact_id',
        'type',
        'number',
        'date',
        'due_date',
        'reference',
        'status',
        'subtotal',
        'tax_amount',
        'total',
        'amount_paid',
        'balance_due',
        'notes',
        'terms',
        'portal_comment',
        'last_reminder_sent_at',
        'currency_code',
        'exchange_rate',
        'journal_entry_id',
        'purchase_order_id',
    ];

    protected function casts(): array
    {
        return [
            'type' => InvoiceType::class,
            'status' => InvoiceStatus::class,
            'date' => 'date:Y-m-d',
            'due_date' => 'date:Y-m-d',
            'last_reminder_sent_at' => 'datetime',
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'balance_due' => 'decimal:2',
            'exchange_rate' => 'decimal:6',
            'last_reminder_sent_at' => 'datetime',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class)->orderBy('sort_order');
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function paymentAllocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    // ── Scopes ─────────────────────────────────────────────────────

    public function scopeOfType($query, InvoiceType $type)
    {
        return $query->where('type', $type);
    }

    public function scopeInvoices($query)
    {
        return $query->where('type', InvoiceType::INVOICE);
    }

    public function scopeQuotes($query)
    {
        return $query->where('type', InvoiceType::QUOTE);
    }

    public function scopeCreditNotes($query)
    {
        return $query->where('type', InvoiceType::CREDIT_NOTE);
    }

    public function scopePurchaseInvoices($query)
    {
        return $query->where('type', InvoiceType::PURCHASE_INVOICE);
    }

    public function scopeDebitNotes($query)
    {
        return $query->where('type', InvoiceType::DEBIT_NOTE);
    }

    public function scopePurchaseOrders($query)
    {
        return $query->where('type', InvoiceType::PURCHASE_ORDER);
    }

    public function scopeUnpaid($query)
    {
        return $query->where('balance_due', '>', 0)
            ->whereNotIn('status', [InvoiceStatus::VOID, InvoiceStatus::CANCELLED]);
    }

    public function scopeOverdue($query)
    {
        return $query->unpaid()
            ->where('due_date', '<', now())
            ->where('type', '!=', InvoiceType::QUOTE);
    }

    // ── Helpers ────────────────────────────────────────────────────

    public function isPaid(): bool
    {
        return $this->balance_due <= 0 && $this->status === InvoiceStatus::PAID;
    }

    public function isOverdue(): bool
    {
        return $this->due_date && $this->due_date->isPast() && $this->balance_due > 0;
    }

    public function isQuote(): bool
    {
        return $this->type === InvoiceType::QUOTE;
    }

    public function isCreditNote(): bool
    {
        return $this->type === InvoiceType::CREDIT_NOTE;
    }

    public function isPurchaseInvoice(): bool
    {
        return $this->type === InvoiceType::PURCHASE_INVOICE;
    }

    public function isDebitNote(): bool
    {
        return $this->type === InvoiceType::DEBIT_NOTE;
    }

    public function isPurchaseOrder(): bool
    {
        return $this->type === InvoiceType::PURCHASE_ORDER;
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'purchase_order_id');
    }

    public function purchaseInvoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'purchase_order_id');
    }
}
