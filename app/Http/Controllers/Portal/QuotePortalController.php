<?php

namespace App\Http\Controllers\Portal;

use App\Models\Invoice;
use App\Services\Sales\QuoteService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class QuotePortalController
{
    public function __construct(
        private QuoteService $quoteService,
    ) {}

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
            'approveUrl' => URL::signedRoute('portal.quotes.approve', ['quote' => $quote->id]),
            'rejectUrl' => URL::signedRoute('portal.quotes.reject', ['quote' => $quote->id]),
        ]);
    }

    public function approve(Request $request, Invoice $quote): RedirectResponse
    {
        $request->validate(['comment' => ['nullable', 'string', 'max:1000']]);

        $quote = Invoice::withoutGlobalScopes()->findOrFail($quote->id);

        try {
            $this->quoteService->approveFromPortal($quote, $request->input('comment'));
        } catch (DomainException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        return redirect()->signedRoute('portal.quotes.show', ['quote' => $quote->id])
            ->with('responded', 'approved');
    }

    public function reject(Request $request, Invoice $quote): RedirectResponse
    {
        $request->validate(['comment' => ['nullable', 'string', 'max:1000']]);

        $quote = Invoice::withoutGlobalScopes()->findOrFail($quote->id);

        try {
            $this->quoteService->rejectFromPortal($quote, $request->input('comment'));
        } catch (DomainException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        return redirect()->signedRoute('portal.quotes.show', ['quote' => $quote->id])
            ->with('responded', 'rejected');
    }
}
