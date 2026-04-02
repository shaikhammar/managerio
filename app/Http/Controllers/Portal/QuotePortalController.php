<?php

namespace App\Http\Controllers\Portal;

use App\Models\Invoice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class QuotePortalController
{
    public function show(Invoice $quote): InertiaResponse
    {
        $quote = Invoice::withoutGlobalScopes()
            ->with(['lines.taxCode', 'contact', 'business'])
            ->findOrFail($quote->id);

        $alreadyResponded = in_array($quote->status->value, ['approved', 'cancelled']);

        return Inertia::render('portal/quote-approval', [
            'quote' => $quote,
            'business' => $quote->business,
            'alreadyResponded' => $alreadyResponded,
        ]);
    }

    public function approve(Request $request, Invoice $quote): RedirectResponse
    {
        return back();
    }

    public function reject(Request $request, Invoice $quote): RedirectResponse
    {
        return back();
    }
}
