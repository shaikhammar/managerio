<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntercompanyTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'source_business_id',
        'target_business_id',
        'source_account_id',
        'target_account_id',
        'amount',
        'date',
        'description',
        'reference',
        'source_journal_entry_id',
        'target_journal_entry_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'date' => 'date:Y-m-d',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────

    public function sourceBusiness(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'source_business_id');
    }

    public function targetBusiness(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'target_business_id');
    }

    public function sourceAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'source_account_id');
    }

    public function targetAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'target_account_id');
    }

    public function sourceJournalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'source_journal_entry_id');
    }

    public function targetJournalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'target_journal_entry_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
