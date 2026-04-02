<?php

namespace App\Http\Controllers\Portal;

use App\Models\Invoice;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response;

class InvoicePortalController
{
    public function show(Invoice $invoice): InertiaResponse
    {
        $invoice = Invoice::withoutGlobalScopes()
            ->with(['lines.taxCode', 'contact', 'business'])
            ->findOrFail($invoice->id);

        return Inertia::render('portal/invoice-view', [
            'invoice' => $invoice,
            'business' => $invoice->business,
            'isVoid' => $invoice->status->value === 'void',
        ]);
    }

    public function pdf(Invoice $invoice): Response
    {
        abort(501, 'Not yet implemented');
    }
}
