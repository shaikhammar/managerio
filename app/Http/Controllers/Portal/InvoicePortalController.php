<?php

namespace App\Http\Controllers\Portal;

use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
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
        $invoice = Invoice::withoutGlobalScopes()
            ->with(['lines.taxCode', 'contact'])
            ->findOrFail($invoice->id);

        $business = $invoice->business;

        $pdf = Pdf::loadView('pdf.invoice', compact('invoice', 'business'));

        return $pdf->stream("{$invoice->number}.pdf");
    }
}
