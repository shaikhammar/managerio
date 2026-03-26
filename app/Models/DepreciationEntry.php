<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DepreciationEntry extends Model
{
    use BelongsToBusiness, HasFactory;

    protected $fillable = [
        'business_id',
        'fixed_asset_id',
        'journal_entry_id',
        'period_start',
        'period_end',
        'depreciation_amount',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date:Y-m-d',
            'period_end' => 'date:Y-m-d',
            'depreciation_amount' => 'decimal:2',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────

    public function fixedAsset(): BelongsTo
    {
        return $this->belongsTo(FixedAsset::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
