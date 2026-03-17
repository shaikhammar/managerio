<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JournalEntry extends Model
{
    use BelongsToBusiness, HasFactory;

    protected $fillable = [
        'business_id',
        'entry_number',
        'date',
        'description',
        'reference',
        'source_type',
        'source_id',
        'is_posted',
        'posted_at',
        'reversal_of_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date:Y-m-d',
            'is_posted' => 'boolean',
            'posted_at' => 'datetime',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────

    public function lines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reversalOf(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'reversal_of_id');
    }

    public function reversals(): HasMany
    {
        return $this->hasMany(JournalEntry::class, 'reversal_of_id');
    }

    // ── Scopes ─────────────────────────────────────────────────────

    public function scopePosted($query)
    {
        return $query->where('is_posted', true);
    }

    public function scopeDraft($query)
    {
        return $query->where('is_posted', false);
    }

    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    // ── Helpers ────────────────────────────────────────────────────

    public function totalDebit(): float
    {
        return (float) $this->lines()->sum('debit');
    }

    public function totalCredit(): float
    {
        return (float) $this->lines()->sum('credit');
    }

    public function isBalanced(): bool
    {
        return bccomp((string) $this->totalDebit(), (string) $this->totalCredit(), 2) === 0;
    }

    public function isReversal(): bool
    {
        return $this->reversal_of_id !== null;
    }

    public function hasBeenReversed(): bool
    {
        return $this->reversals()->exists();
    }
}
