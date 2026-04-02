<?php

namespace App\Http\Controllers\Sales;

use App\Domain\Accounting\Enums\AccountType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\InvoiceRequest;
use App\Mail\InvoiceMail;
use App\Models\Account;
use App\Models\Contact;
use App\Models\Invoice;
use App\Models\TaxCode;
use App\Services\MailService;
use App\Services\Sales\InvoiceService;
use Barryvdh\DomPDF\Facade\Pdf;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response;

class InvoiceController extends Controller
{
    public function __construct(
        private InvoiceService $invoiceService,
        private MailService $mailService,
    ) {}

    public function index(Request $request): InertiaResponse
    {
        $invoices = Invoice::query()
            ->invoices()
            ->with('contact')
            ->when($request->search, fn ($q, $s) => $q->where('number', 'ilike', "%{$s}%"))
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->orderByDesc('date')
            ->paginate(25);

        return Inertia::render('sales/invoices/index', [
            'invoices' => $invoices,
            'filters' => $request->only('search', 'status'),
        ]);
    }

    public function create(): InertiaResponse
    {
        return Inertia::render('sales/invoices/create', [
            'customers' => Contact::query()->customers()->active()->orderBy('name')->get(['id', 'name']),
            'accounts' => Account::query()->active()->whereIn('type', [AccountType::REVENUE, AccountType::EXPENSE])->orderBy('code')->get(['id', 'code', 'name', 'type']),
            'taxCodes' => TaxCode::query()->active()->orderBy('name')->get(['id', 'name', 'rate']),
        ]);
    }

    public function store(InvoiceRequest $request): RedirectResponse
    {
        $this->authorize('create', Invoice::class);

        $business = $request->user()->currentBusiness();
        $invoice = $this->invoiceService->create($business, $request->validated());

        return redirect()->route('sales.invoices.show', $invoice)
            ->with('success', 'Invoice created successfully.');
    }

    public function show(Invoice $invoice): InertiaResponse
    {
        return Inertia::render('sales/invoices/show', [
            'invoice' => $invoice->load(['lines.account', 'lines.taxCode', 'contact', 'journalEntry.lines.account', 'paymentAllocations.payment']),
        ]);
    }

    public function edit(Invoice $invoice): InertiaResponse
    {
        return Inertia::render('sales/invoices/create', [
            'invoice' => $invoice->load('lines'),
            'customers' => Contact::query()->customers()->active()->orderBy('name')->get(['id', 'name']),
            'accounts' => Account::query()->active()->whereIn('type', [AccountType::REVENUE, AccountType::EXPENSE])->orderBy('code')->get(['id', 'code', 'name', 'type']),
            'taxCodes' => TaxCode::query()->active()->orderBy('name')->get(['id', 'name', 'rate']),
        ]);
    }

    public function update(InvoiceRequest $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('update', $invoice);

        try {
            $this->invoiceService->update($invoice, $request->validated());
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('sales.invoices.show', $invoice)
            ->with('success', 'Invoice updated successfully.');
    }

    public function post(Invoice $invoice): RedirectResponse
    {
        $this->authorize('update', $invoice);

        try {
            $this->invoiceService->postInvoice($invoice->load('lines'));
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Invoice {$invoice->number} posted successfully.");
    }

    public function void(Invoice $invoice): RedirectResponse
    {
        $this->authorize('void', $invoice);

        $this->invoiceService->void($invoice);

        return back()->with('success', 'Invoice voided successfully.');
    }

    public function pdf(Invoice $invoice): Response
    {
        $this->authorize('view', $invoice);

        $invoice->load(['lines.account', 'lines.taxCode', 'contact']);
        $business = $invoice->business;

        $pdf = Pdf::loadView('pdf.invoice', compact('invoice', 'business'));

        return $pdf->download("{$invoice->number}.pdf");
    }

    public function sendEmail(Request $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('view', $invoice);

        $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $business = $invoice->business;

        if (! $business->hasEmailConfigured()) {
            return back()->with('error', 'Email is not configured. Please set up SMTP in Settings → Email.');
        }

        $invoice->load(['lines.account', 'lines.taxCode', 'contact']);

        $this->mailService->mailerFor($business)->to($request->email)->send(
            new InvoiceMail($invoice, $business, $invoice->contact?->name ?? $request->email),
        );

        return back()->with('success', "Invoice {$invoice->number} sent to {$request->email}.");
    }

    public function portalLink(Invoice $invoice): JsonResponse
    {
        $this->authorize('view', $invoice);

        return response()->json(['url' => $this->invoiceService->generatePortalUrl($invoice)]);
    }

    public function destroy(Invoice $invoice): RedirectResponse
    {
        $this->authorize('delete', $invoice);

        if ($invoice->amount_paid > 0) {
            return back()->with('error', 'Cannot delete an invoice with payments.');
        }

        $invoice->lines()->delete();
        $invoice->delete();

        return redirect()->route('sales.invoices.index')
            ->with('success', 'Invoice deleted successfully.');
    }
}
