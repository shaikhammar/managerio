<?php

namespace App\Models;

use App\Domain\Shared\Enums\BusinessRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Business extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'legal_name',
        'tax_number',
        'email',
        'phone',
        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'postal_code',
        'country',
        'currency_code',
        'fiscal_year_start',
        'logo_path',
    ];

    protected function casts(): array
    {
        return [
            'fiscal_year_start' => 'integer',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    public function taxCodes(): HasMany
    {
        return $this->hasMany(TaxCode::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function bankTransactions(): HasMany
    {
        return $this->hasMany(BankTransaction::class);
    }

    public function bankReconciliations(): HasMany
    {
        return $this->hasMany(BankReconciliation::class);
    }

    public function numberSequences(): HasMany
    {
        return $this->hasMany(NumberSequence::class);
    }

    // ── Helpers ────────────────────────────────────────────────────

    public function owner(): ?User
    {
        return $this->users()
            ->wherePivot('role', BusinessRole::OWNER->value)
            ->first();
    }

    public function getUserRole(User $user): ?BusinessRole
    {
        $pivot = $this->users()->where('user_id', $user->id)->first()?->pivot;

        return $pivot ? BusinessRole::from($pivot->role) : null;
    }

    public function hasUser(User $user): bool
    {
        return $this->users()->where('user_id', $user->id)->exists();
    }
}
