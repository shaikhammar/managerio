<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RateCardVolumeTier extends Model
{
    protected $fillable = [
        'rate_card_id',
        'minimum_words',
        'unit_rate',
    ];

    protected function casts(): array
    {
        return [
            'minimum_words' => 'integer',
            'unit_rate' => 'decimal:4',
        ];
    }

    public function rateCard(): BelongsTo
    {
        return $this->belongsTo(RateCard::class);
    }
}
