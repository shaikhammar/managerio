<?php

namespace App\Models;

use App\Domain\Accounting\Enums\RecurringFrequency;
use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecurringJournalEntry extends Model
{
    use BelongsToBusiness, HasFactory;

    protected $fillable = [
        'business_id',
        'name',
        'description',
        'frequency',
        'start_date',
        'end_date',
        'next_run_date',
        'last_run_at',
        'day_of_month',
        'is_active',
        'template_lines',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'frequency' => RecurringFrequency::class,
            'start_date' => 'date:Y-m-d',
            'end_date' => 'date:Y-m-d',
            'next_run_date' => 'date:Y-m-d',
            'last_run_at' => 'datetime',
            'is_active' => 'boolean',
            'template_lines' => 'array',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ── Scopes ─────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDue($query)
    {
        return $query->active()->where('next_run_date', '<=', now()->toDateString());
    }

    // ── Helpers ────────────────────────────────────────────────────

    public function isExpired(): bool
    {
        return $this->end_date !== null && $this->end_date->isPast();
    }
}
