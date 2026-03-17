<?php

namespace App\Models;

use App\Domain\Accounting\Enums\AccountSubType;
use App\Domain\Accounting\Enums\AccountType;
use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    use BelongsToBusiness, HasFactory;

    protected $fillable = [
        'business_id',
        'code',
        'name',
        'type',
        'sub_type',
        'description',
        'is_system',
        'is_active',
        'parent_id',
    ];

    protected function casts(): array
    {
        return [
            'type' => AccountType::class,
            'sub_type' => AccountSubType::class,
            'is_system' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Account::class, 'parent_id');
    }

    public function journalEntryLines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    public function invoiceLines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class);
    }

    // ── Scopes ─────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOfType($query, AccountType $type)
    {
        return $query->where('type', $type);
    }

    public function scopeOfSubType($query, AccountSubType $subType)
    {
        return $query->where('sub_type', $subType);
    }

    public function scopeBankAccounts($query)
    {
        return $query->where('sub_type', AccountSubType::BANK);
    }

    // ── Helpers ────────────────────────────────────────────────────

    public function isSystemAccount(): bool
    {
        return $this->is_system;
    }

    public function isBankAccount(): bool
    {
        return $this->sub_type === AccountSubType::BANK;
    }
}
