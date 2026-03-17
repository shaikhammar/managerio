<?php

namespace App\Services\Accounting;

use App\Models\Business;
use App\Models\NumberSequence;
use Illuminate\Support\Facades\DB;

class NumberSequenceService
{
    /**
     * Get the next formatted number for a given type.
     * Uses database-level locking to prevent duplicates under concurrency.
     */
    public function getNext(Business $business, string $type): string
    {
        return DB::transaction(function () use ($business, $type) {
            $sequence = NumberSequence::withoutGlobalScopes()
                ->where('business_id', $business->id)
                ->where('type', $type)
                ->lockForUpdate()
                ->first();

            if (! $sequence) {
                $sequence = NumberSequence::withoutGlobalScopes()->create([
                    'business_id' => $business->id,
                    'type' => $type,
                    'prefix' => $this->getDefaultPrefix($type),
                    'next_number' => 1,
                    'padding' => 4,
                ]);
            }

            return $sequence->generateNext();
        });
    }

    private function getDefaultPrefix(string $type): string
    {
        return match ($type) {
            'invoice' => 'INV',
            'quote' => 'QT',
            'credit_note' => 'CN',
            'purchase_invoice' => 'PI',
            'payment', 'receipt' => 'PAY',
            'journal_entry' => 'JE',
            default => strtoupper(substr($type, 0, 3)),
        };
    }
}
