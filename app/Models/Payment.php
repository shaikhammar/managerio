<?php

namespace App\Models;

use App\Domain\Payments\Enums\PaymentType;
use App\Domain\Shared\Concerns\Auditable;
use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    use Auditable, BelongsToBusiness, HasFactory;

    protected string $auditLabel = 'number';

    protected $fillable = [
        'business_id',
        'contact_id',
        'type',
        'number',
        'date',
        'amount',
        'bank_account_id',
        'reference',
        'description',
        'journal_entry_id',
    ];

    protected function casts(): array
    {
        return [
            'type' => PaymentType::class,
            'date' => 'date:Y-m-d',
            'amount' => 'decimal:2',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'bank_account_id');
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    public function bankTransaction(): BelongsTo
    {
        return $this->belongsTo(BankTransaction::class);
    }

    // ── Scopes ─────────────────────────────────────────────────────

    public function scopeReceipts($query)
    {
        return $query->where('type', PaymentType::RECEIPT);
    }

    public function scopePayments($query)
    {
        return $query->where('type', PaymentType::PAYMENT);
    }

    // ── Helpers ────────────────────────────────────────────────────

    public function isReceipt(): bool
    {
        return $this->type === PaymentType::RECEIPT;
    }

    public function isPayment(): bool
    {
        return $this->type === PaymentType::PAYMENT;
    }

    public function allocatedAmount(): float
    {
        return (float) $this->allocations()->sum('amount');
    }

    public function unallocatedAmount(): float
    {
        return (float) $this->amount - $this->allocatedAmount();
    }
}
