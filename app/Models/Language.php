<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Language extends Model
{
    use BelongsToBusiness, HasFactory;

    protected $fillable = [
        'business_id',
        'code',
        'name',
        'native_name',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /** @return HasMany<LanguagePair, $this> */
    public function sourceLanguagePairs(): HasMany
    {
        return $this->hasMany(LanguagePair::class, 'source_language_id');
    }

    /** @return HasMany<LanguagePair, $this> */
    public function targetLanguagePairs(): HasMany
    {
        return $this->hasMany(LanguagePair::class, 'target_language_id');
    }
}
