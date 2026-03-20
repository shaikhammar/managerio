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

class CreditNoteController extends Controller
{
    public function __construct(
        private InvoiceService $invoiceService,
    ) {}

    public function index(Request $request)
    {
        $creditNotes = Invoice::query()
            ->creditNotes()
            ->with('contact')
            ->when($request->search, fn ($q, $s) => $q->where('number', 'ilike', "%{$s}%"))
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->orderByDesc('date')
            ->paginate(25);

        return Inertia::render('sales/credit-notes/index', [
            'creditNotes' => $creditNotes,
            'filters' => $request->only('search', 'status'),
        ]);
    }

    public function create()
    {
        return Inertia::render('sales/credit-notes/create', [
            'customers' => Contact::query()->customers()->active()->orderBy('name')->get(['id', 'name']),
            'accounts' => Account::query()->active()->whereIn('type', ['revenue', 'expense'])->orderBy('code')->get(['id', 'code', 'name', 'type']),
            'taxCodes' => TaxCode::query()->active()->orderBy('name')->get(['id', 'name', 'rate']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'contact_id' => 'required|exists:contacts,id',
            'date' => 'required|date',
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'lines' => 'required|array|min:1',
            'lines.*.account_id' => 'required|exists:accounts,id',
            'lines.*.description' => 'required|string',
            'lines.*.quantity' => 'required|numeric|min:0.0001',
            'lines.*.unit_price' => 'required|numeric|min:0',
            'lines.*.discount_percent' => 'nullable|numeric|between:0,100',
            'lines.*.tax_code_id' => 'nullable|exists:tax_codes,id',
        ]);

        $business = $request->user()->currentBusiness();
        $creditNote = $this->invoiceService->createCreditNote($business, $validated);

        return redirect()->route('sales.credit-notes.show', $creditNote)
            ->with('success', 'Credit note created successfully.');
    }

    public function show(Invoice $creditNote)
    {
        if (! $creditNote->isCreditNote()) {
            abort(404);
        }

        return Inertia::render('sales/credit-notes/show', [
            'creditNote' => $creditNote->load(['lines.account', 'lines.taxCode', 'contact', 'journalEntry.lines.account']),
        ]);
    }

    public function edit(Invoice $creditNote)
    {
        if (! $creditNote->isCreditNote()) {
            abort(404);
        }

        return Inertia::render('sales/credit-notes/create', [
            'creditNote' => $creditNote->load('lines'),
            'customers' => Contact::query()->customers()->active()->orderBy('name')->get(['id', 'name']),
            'accounts' => Account::query()->active()->whereIn('type', ['revenue', 'expense'])->orderBy('code')->get(['id', 'code', 'name', 'type']),
            'taxCodes' => TaxCode::query()->active()->orderBy('name')->get(['id', 'name', 'rate']),
        ]);
    }

    public function update(Request $request, Invoice $creditNote)
    {
        if (! $creditNote->isCreditNote()) {
            abort(404);
        }

        $validated = $request->validate([
            'contact_id' => 'required|exists:contacts,id',
            'date' => 'required|date',
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'lines' => 'required|array|min:1',
            'lines.*.account_id' => 'required|exists:accounts,id',
            'lines.*.description' => 'required|string',
            'lines.*.quantity' => 'required|numeric|min:0.0001',
            'lines.*.unit_price' => 'required|numeric|min:0',
            'lines.*.discount_percent' => 'nullable|numeric|between:0,100',
            'lines.*.tax_code_id' => 'nullable|exists:tax_codes,id',
        ]);

        $this->invoiceService->update($creditNote, $validated);

        return redirect()->route('sales.credit-notes.show', $creditNote)
            ->with('success', 'Credit note updated successfully.');
    }

    public function destroy(Invoice $creditNote)
    {
        if (! $creditNote->isCreditNote()) {
            abort(404);
        }

        $creditNote->lines()->delete();
        $creditNote->delete();

        return redirect()->route('sales.credit-notes.index')
            ->with('success', 'Credit note deleted successfully.');
    }
}
