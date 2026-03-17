<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Contact;
use App\Models\Invoice;
use App\Models\TaxCode;
use App\Services\Sales\InvoiceService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InvoiceController extends Controller
{
    public function __construct(
        private InvoiceService $invoiceService,
    ) {}

    public function index(Request $request)
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

    public function create()
    {
        return Inertia::render('sales/invoices/create', [
            'customers' => Contact::query()->customers()->active()->orderBy('name')->get(['id', 'name']),
            'accounts' => Account::query()->active()->whereIn('type', ['revenue', 'expense'])->orderBy('code')->get(['id', 'code', 'name']),
            'taxCodes' => TaxCode::query()->active()->orderBy('name')->get(['id', 'name', 'rate']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'contact_id' => 'required|exists:contacts,id',
            'date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:date',
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'terms' => 'nullable|string',
            'lines' => 'required|array|min:1',
            'lines.*.account_id' => 'required|exists:accounts,id',
            'lines.*.description' => 'required|string',
            'lines.*.quantity' => 'required|numeric|min:0.0001',
            'lines.*.unit_price' => 'required|numeric|min:0',
            'lines.*.discount_percent' => 'nullable|numeric|between:0,100',
            'lines.*.tax_code_id' => 'nullable|exists:tax_codes,id',
        ]);

        $business = $request->user()->currentBusiness();
        $invoice = $this->invoiceService->create($business, $validated);

        return redirect()->route('sales.invoices.show', $invoice)
            ->with('success', 'Invoice created successfully.');
    }

    public function show(Invoice $invoice)
    {
        return Inertia::render('sales/invoices/show', [
            'invoice' => $invoice->load(['lines.account', 'lines.taxCode', 'contact', 'journalEntry.lines.account', 'paymentAllocations.payment']),
        ]);
    }

    public function edit(Invoice $invoice)
    {
        return Inertia::render('sales/invoices/create', [
            'invoice' => $invoice->load('lines'),
            'customers' => Contact::query()->customers()->active()->orderBy('name')->get(['id', 'name']),
            'accounts' => Account::query()->active()->whereIn('type', ['revenue', 'expense'])->orderBy('code')->get(['id', 'code', 'name']),
            'taxCodes' => TaxCode::query()->active()->orderBy('name')->get(['id', 'name', 'rate']),
        ]);
    }

    public function update(Request $request, Invoice $invoice)
    {
        $validated = $request->validate([
            'contact_id' => 'required|exists:contacts,id',
            'date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:date',
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'terms' => 'nullable|string',
            'lines' => 'required|array|min:1',
            'lines.*.account_id' => 'required|exists:accounts,id',
            'lines.*.description' => 'required|string',
            'lines.*.quantity' => 'required|numeric|min:0.0001',
            'lines.*.unit_price' => 'required|numeric|min:0',
            'lines.*.discount_percent' => 'nullable|numeric|between:0,100',
            'lines.*.tax_code_id' => 'nullable|exists:tax_codes,id',
        ]);

        $this->invoiceService->update($invoice, $validated);

        return redirect()->route('sales.invoices.show', $invoice)
            ->with('success', 'Invoice updated successfully.');
    }

    public function void(Invoice $invoice)
    {
        $this->invoiceService->void($invoice);

        return back()->with('success', 'Invoice voided successfully.');
    }

    public function destroy(Invoice $invoice)
    {
        if ($invoice->amount_paid > 0) {
            return back()->with('error', 'Cannot delete an invoice with payments.');
        }

        $invoice->lines()->delete();
        $invoice->delete();

        return redirect()->route('sales.invoices.index')
            ->with('success', 'Invoice deleted successfully.');
    }
}
