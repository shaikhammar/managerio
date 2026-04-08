<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExchangeRate extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'business_id',
        'currency_code',
        'rate',
        'date',
    ];

    protected function casts(): array
    {
        return [
            'rate' => 'decimal:6',
            'date' => 'date:Y-m-d',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}
