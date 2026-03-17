<?php

namespace App\Models\Concerns;

use App\Models\Business;
use App\Models\Scopes\BusinessScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Trait for models that belong to a business (tenant).
 *
 * Automatically applies a global scope to filter queries by the current business.
 * Must be used on models that have a `business_id` column.
 */
trait BelongsToBusiness
{
    public static function bootBelongsToBusiness(): void
    {
        static::addGlobalScope(new BusinessScope);

        static::creating(function ($model) {
            if (! $model->business_id && session()->has('current_business_id')) {
                $model->business_id = session('current_business_id');
            }
        });
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}
