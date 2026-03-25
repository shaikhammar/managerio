<?php

namespace App\Models;

use App\Domain\Accounting\Enums\AssetStatus;
use App\Domain\Accounting\Enums\DepreciationMethod;
use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FixedAsset extends Model
{
    use BelongsToBusiness, HasFactory;

    protected $fillable = [
        'business_id',
        'asset_account_id',
        'accumulated_depreciation_account_id',
        'depreciation_expense_account_id',
        'name',
        'description',
        'asset_tag',
        'purchase_date',
        'purchase_cost',
        'salvage_value',
        'useful_life_months',
        'depreciation_method',
        'status',
        'disposal_date',
        'disposal_proceeds',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'depreciation_method' => DepreciationMethod::class,
            'status' => AssetStatus::class,
            'purchase_date' => 'date:Y-m-d',
            'disposal_date' => 'date:Y-m-d',
            'purchase_cost' => 'decimal:2',
            'salvage_value' => 'decimal:2',
            'disposal_proceeds' => 'decimal:2',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────

    public function assetAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'asset_account_id');
    }

    public function accumulatedDepreciationAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'accumulated_depreciation_account_id');
    }

    public function depreciationExpenseAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'depreciation_expense_account_id');
    }

    public function depreciationEntries(): HasMany
    {
        return $this->hasMany(DepreciationEntry::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ── Scopes ─────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', AssetStatus::Active);
    }

    // ── Computed Helpers ───────────────────────────────────────────

    public function accumulatedDepreciation(): string
    {
        return (string) $this->depreciationEntries()->sum('depreciation_amount');
    }

    public function bookValue(): string
    {
        return bcsub((string) $this->purchase_cost, $this->accumulatedDepreciation(), 2);
    }

    public function isFullyDepreciated(): bool
    {
        return bccomp($this->bookValue(), (string) $this->salvage_value, 2) <= 0;
    }
}
