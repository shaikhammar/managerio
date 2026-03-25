<?php

namespace App\Http\Controllers\Sales;

use App\Domain\Sales\Enums\InvoiceStatus;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BulkQuoteController extends Controller
{
    public function deleteDrafts(Request $request): RedirectResponse
    {
        $this->authorize('create', Invoice::class);

        $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $quotes = Invoice::query()
            ->quotes()
            ->whereIn('id', $request->ids)
            ->where('status', InvoiceStatus::DRAFT)
            ->get();

        $count = $quotes->count();

        foreach ($quotes as $quote) {
            $quote->lines()->delete();
            $quote->delete();
        }

        return back()->with('success', "{$count} draft quote(s) deleted.");
    }
}
