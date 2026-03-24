<?php

namespace App\Listeners;

use App\Events\InvoicePosted;
use App\Events\JournalEntryPosted;
use App\Events\PaymentReceived;
use App\Models\Business;
use Illuminate\Support\Facades\Cache;

class InvalidateReportCache
{
    public function handle(InvoicePosted|PaymentReceived|JournalEntryPosted $event): void
    {
        $business = $this->resolveBusiness($event);

        if (! $business) {
            return;
        }

        // Record when this business's financial data last changed.
        // The GenerateReport job embeds a generation counter in cache keys
        // so any report cached before this timestamp is treated as stale.
        Cache::put(
            "report_invalidated_at:{$business->id}",
            now()->toIso8601String(),
            now()->addDays(30),
        );
    }

    private function resolveBusiness(InvoicePosted|PaymentReceived|JournalEntryPosted $event): ?Business
    {
        return match (true) {
            $event instanceof InvoicePosted => $event->invoice->business,
            $event instanceof PaymentReceived => $event->payment->business,
            $event instanceof JournalEntryPosted => $event->entry->business,
        };
    }
}
