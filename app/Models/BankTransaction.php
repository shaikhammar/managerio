<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankTransaction extends Model
{
    use BelongsToBusiness, HasFactory;

    protected $fillable = [
        'business_id',
        'bank_account_id',
        'date',
        'description',
        'amount',
        'reference',
        'is_reconciled',
        'reconciled_at',
        'journal_entry_id',
        'payment_id',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'amount' => 'decimal:2',
            'is_reconciled' => 'boolean',
            'reconciled_at' => 'datetime',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'bank_account_id');
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    // ── Scopes ─────────────────────────────────────────────────────

    public function scopeReconciled($query)
    {
        return $query->where('is_reconciled', true);
    }

    public function scopeUnreconciled($query)
    {
        return $query->where('is_reconciled', false);
    }

    public function scopeDeposits($query)
    {
        return $query->where('amount', '>', 0);
    }

    public function scopeWithdrawals($query)
    {
        return $query->where('amount', '<', 0);
    }

    // ── Helpers ────────────────────────────────────────────────────

    public function isDeposit(): bool
    {
        return $this->amount > 0;
    }

    public function isWithdrawal(): bool
    {
        return $this->amount < 0;
    }

    public function markReconciled(): void
    {
        $this->update([
            'is_reconciled' => true,
            'reconciled_at' => now(),
        ]);
    }
}
