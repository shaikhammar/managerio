<?php

namespace App\Http\Controllers\Sales;

use App\Domain\Sales\Enums\InvoiceStatus;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BulkInvoiceController extends Controller
{
    public function markSent(Request $request): RedirectResponse
    {
        $this->authorize('create', Invoice::class);

        $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $updated = Invoice::query()
            ->invoices()
            ->whereIn('id', $request->ids)
            ->where('status', InvoiceStatus::DRAFT)
            ->update(['status' => InvoiceStatus::SENT]);

        return back()->with('success', "{$updated} invoice(s) marked as sent.");
    }

    public function deleteDrafts(Request $request): RedirectResponse
    {
        $this->authorize('create', Invoice::class);

        $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $invoices = Invoice::query()
            ->invoices()
            ->whereIn('id', $request->ids)
            ->where('status', InvoiceStatus::DRAFT)
            ->where('amount_paid', 0)
            ->get();

        $count = $invoices->count();

        foreach ($invoices as $invoice) {
            $invoice->lines()->delete();
            $invoice->delete();
        }

        return back()->with('success', "{$count} draft invoice(s) deleted.");
    }
}
