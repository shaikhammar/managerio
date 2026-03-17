<?php

namespace App\Models;

use App\Domain\Shared\Enums\BusinessRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────

    public function businesses(): BelongsToMany
    {
        return $this->belongsToMany(Business::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    public function ownedBusinesses(): BelongsToMany
    {
        return $this->businesses()->wherePivot('role', BusinessRole::OWNER->value);
    }

    // ── Helpers ────────────────────────────────────────────────────

    public function currentBusiness(): ?Business
    {
        if (session()->has('current_business_id')) {
            return Business::find(session('current_business_id'));
        }

        return $this->businesses()->first();
    }

    public function roleIn(Business $business): ?BusinessRole
    {
        $pivot = $this->businesses()
            ->where('business_id', $business->id)
            ->first()?->pivot;

        return $pivot ? BusinessRole::from($pivot->role) : null;
    }

    public function belongsToBusiness(Business $business): bool
    {
        return $this->businesses()->where('business_id', $business->id)->exists();
    }

    public function canEditIn(Business $business): bool
    {
        $role = $this->roleIn($business);

        return $role && $role->canEdit();
    }

    public function canManageIn(Business $business): bool
    {
        $role = $this->roleIn($business);

        return $role && $role->canManage();
    }
}
