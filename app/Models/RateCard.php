<?php

namespace App\Models;

use App\Domain\Translation\Enums\BillingUnit;
use App\Domain\Translation\Enums\RateCardType;
use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RateCard extends Model
{
    use BelongsToBusiness, HasFactory;

    protected $fillable = [
        'business_id',
        'type',
        'contact_id',
        'language_pair_id',
        'service_type_id',
        'unit_rate',
        'unit',
        'minimum_fee',
        'rush_multiplier',
        'rush_fixed_surcharge',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'type' => RateCardType::class,
            'unit' => BillingUnit::class,
            'unit_rate' => 'decimal:4',
            'minimum_fee' => 'decimal:2',
            'rush_multiplier' => 'decimal:2',
            'rush_fixed_surcharge' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function languagePair(): BelongsTo
    {
        return $this->belongsTo(LanguagePair::class);
    }

    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(ServiceType::class);
    }

    public function volumeTiers(): HasMany
    {
        return $this->hasMany(RateCardVolumeTier::class)->orderBy('minimum_words');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOfType($query, RateCardType $type)
    {
        return $query->where('type', $type->value);
    }
}
