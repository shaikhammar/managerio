<?php

namespace App\Services\Currency;

use App\Models\Business;
use App\Models\ExchangeRate;
use Illuminate\Support\Carbon;

class CurrencyService
{
    /**
     * Convert a foreign currency amount to base currency using the given rate.
     */
    public function toBase(string $foreignAmount, string $exchangeRate): string
    {
        return bcmul($foreignAmount, $exchangeRate, 2);
    }

    /**
     * Look up the most recent exchange rate for a currency on or before a given date.
     * Returns 1.0 if the currency matches the business base currency or no rate is found.
     */
    public function getRate(Business $business, string $currencyCode, Carbon $date): float
    {
        if ($currencyCode === $business->currency_code) {
            return 1.0;
        }

        $record = ExchangeRate::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->where('currency_code', $currencyCode)
            ->where('date', '<=', $date->format('Y-m-d'))
            ->orderByDesc('date')
            ->first();

        return $record ? (float) $record->rate : 1.0;
    }

    /**
     * Check whether a currency code is the same as the business base currency.
     */
    public function isBaseCurrency(Business $business, string $currencyCode): bool
    {
        return $currencyCode === $business->currency_code;
    }
}
