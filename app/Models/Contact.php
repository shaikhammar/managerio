<?php

namespace App\Models;

use App\Domain\Contacts\Enums\ContactType;
use App\Domain\Shared\Concerns\Auditable;
use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contact extends Model
{
    use Auditable, BelongsToBusiness, HasFactory;

    protected string $auditLabel = 'name';

    protected $fillable = [
        'business_id',
        'type',
        'name',
        'email',
        'phone',
        'tax_number',
        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'postal_code',
        'country',
        'currency_code',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'type' => ContactType::class,
            'is_active' => 'boolean',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    // ── Scopes ─────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeCustomers($query)
    {
        return $query->whereIn('type', [ContactType::CUSTOMER, ContactType::BOTH]);
    }

    public function scopeSuppliers($query)
    {
        return $query->whereIn('type', [ContactType::SUPPLIER, ContactType::BOTH]);
    }

    // ── Helpers ────────────────────────────────────────────────────

    public function isCustomer(): bool
    {
        return in_array($this->type, [ContactType::CUSTOMER, ContactType::BOTH]);
    }

    public function isSupplier(): bool
    {
        return in_array($this->type, [ContactType::SUPPLIER, ContactType::BOTH]);
    }

    public function fullAddress(): string
    {
        return collect([
            $this->address_line_1,
            $this->address_line_2,
            $this->city,
            $this->state,
            $this->postal_code,
            $this->country,
        ])->filter()->implode(', ');
    }
}
