<?php

namespace App\Models;

use App\Domain\Translation\Enums\BillingUnit;
use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceType extends Model
{
    use BelongsToBusiness, HasFactory;

    protected $fillable = [
        'business_id',
        'name',
        'code',
        'description',
        'default_unit',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'default_unit' => BillingUnit::class,
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
