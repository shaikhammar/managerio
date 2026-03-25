<?php

namespace App\Http\Controllers\Purchases;

use App\Domain\Accounting\Enums\AccountType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Purchases\PurchaseInvoiceRequest;
use App\Models\Account;
use App\Models\Contact;
use App\Models\Invoice;
use App\Models\TaxCode;
use App\Services\Sales\InvoiceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseInvoiceController extends Controller
{
    public function __construct(
        private InvoiceService $invoiceService,
    ) {}

    public function index(Request $request): Response
    {
        $invoices = Invoice::query()
            ->purchaseInvoices()
            ->with('contact')
            ->when($request->search, fn ($q, $s) => $q->where('number', 'ilike', "%{$s}%"))
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->orderByDesc('date')
            ->paginate(25);

        return Inertia::render('purchases/invoices/index', [
            'invoices' => $invoices,
            'filters' => $request->only('search', 'status'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('purchases/invoices/create', [
            'suppliers' => Contact::query()->suppliers()->active()->orderBy('name')->get(['id', 'name']),
            'accounts' => Account::query()->active()->whereIn('type', [AccountType::EXPENSE, AccountType::ASSET])->orderBy('code')->get(['id', 'code', 'name', 'type']),
            'taxCodes' => TaxCode::query()->active()->orderBy('name')->get(['id', 'name', 'rate']),
        ]);
    }

    public function store(PurchaseInvoiceRequest $request): RedirectResponse
    {
        $this->authorize('create', Invoice::class);

        $business = $request->user()->currentBusiness();
        $invoice = $this->invoiceService->createPurchaseInvoice($business, $request->validated());

        return redirect()->route('purchases.purchase-invoices.show', $invoice)
            ->with('success', 'Purchase invoice created successfully.');
    }

    public function show(Invoice $invoice): Response|RedirectResponse
    {
        if (! $invoice->isPurchaseInvoice()) {
            abort(404);
        }

        return Inertia::render('purchases/invoices/show', [
            'invoice' => $invoice->load(['lines.account', 'lines.taxCode', 'contact', 'journalEntry.lines.account']),
        ]);
    }

    public function edit(Invoice $invoice): Response|RedirectResponse
    {
        if (! $invoice->isPurchaseInvoice()) {
            abort(404);
        }

        return Inertia::render('purchases/invoices/create', [
            'invoice' => $invoice->load('lines'),
            'suppliers' => Contact::query()->suppliers()->active()->orderBy('name')->get(['id', 'name']),
            'accounts' => Account::query()->active()->whereIn('type', [AccountType::EXPENSE, AccountType::ASSET])->orderBy('code')->get(['id', 'code', 'name', 'type']),
            'taxCodes' => TaxCode::query()->active()->orderBy('name')->get(['id', 'name', 'rate']),
        ]);
    }

    public function update(PurchaseInvoiceRequest $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('update', $invoice);

        if (! $invoice->isPurchaseInvoice()) {
            abort(404);
        }

        $this->invoiceService->update($invoice, $request->validated());

        return redirect()->route('purchases.purchase-invoices.show', $invoice)
            ->with('success', 'Purchase invoice updated successfully.');
    }

    public function destroy(Invoice $invoice): RedirectResponse
    {
        $this->authorize('delete', $invoice);

        if (! $invoice->isPurchaseInvoice()) {
            abort(404);
        }

        if ($invoice->amount_paid > 0) {
            return back()->with('error', 'Cannot delete an invoice with payments.');
        }

        $invoice->lines()->delete();
        $invoice->delete();

        return redirect()->route('purchases.purchase-invoices.index')
            ->with('success', 'Purchase invoice deleted successfully.');
    }
}
