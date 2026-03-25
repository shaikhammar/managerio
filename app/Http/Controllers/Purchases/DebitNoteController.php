<?php

namespace App\Http\Controllers\Purchases;

use App\Domain\Accounting\Enums\AccountType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Purchases\DebitNoteRequest;
use App\Models\Account;
use App\Models\Contact;
use App\Models\Invoice;
use App\Models\TaxCode;
use App\Services\Sales\InvoiceService;
use Barryvdh\DomPDF\Facade\Pdf;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response;

class DebitNoteController extends Controller
{
    public function __construct(
        private InvoiceService $invoiceService,
    ) {}

    public function index(Request $request): InertiaResponse
    {
        $debitNotes = Invoice::query()
            ->debitNotes()
            ->with('contact')
            ->when($request->search, fn ($q, $s) => $q->where('number', 'ilike', "%{$s}%"))
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->orderByDesc('date')
            ->paginate(25);

        return Inertia::render('purchases/debit-notes/index', [
            'debitNotes' => $debitNotes,
            'filters' => $request->only('search', 'status'),
        ]);
    }

    public function create(): InertiaResponse
    {
        return Inertia::render('purchases/debit-notes/create', [
            'suppliers' => Contact::query()->suppliers()->active()->orderBy('name')->get(['id', 'name']),
            'accounts' => Account::query()->active()->whereIn('type', [AccountType::EXPENSE, AccountType::ASSET])->orderBy('code')->get(['id', 'code', 'name', 'type']),
            'taxCodes' => TaxCode::query()->active()->orderBy('name')->get(['id', 'name', 'rate']),
        ]);
    }

    public function store(DebitNoteRequest $request): RedirectResponse
    {
        $this->authorize('create', Invoice::class);

        $business = $request->user()->currentBusiness();
        $debitNote = $this->invoiceService->createDebitNote($business, $request->validated());

        return redirect()->route('purchases.debit-notes.show', $debitNote)
            ->with('success', 'Debit note created successfully.');
    }

    public function show(Invoice $debitNote): InertiaResponse|RedirectResponse
    {
        if (! $debitNote->isDebitNote()) {
            abort(404);
        }

        return Inertia::render('purchases/debit-notes/show', [
            'debitNote' => $debitNote->load(['lines.account', 'lines.taxCode', 'contact', 'journalEntry.lines.account']),
        ]);
    }

    public function edit(Invoice $debitNote): InertiaResponse|RedirectResponse
    {
        if (! $debitNote->isDebitNote()) {
            abort(404);
        }

        return Inertia::render('purchases/debit-notes/create', [
            'debitNote' => $debitNote->load('lines'),
            'suppliers' => Contact::query()->suppliers()->active()->orderBy('name')->get(['id', 'name']),
            'accounts' => Account::query()->active()->whereIn('type', [AccountType::EXPENSE, AccountType::ASSET])->orderBy('code')->get(['id', 'code', 'name', 'type']),
            'taxCodes' => TaxCode::query()->active()->orderBy('name')->get(['id', 'name', 'rate']),
        ]);
    }

    public function update(DebitNoteRequest $request, Invoice $debitNote): RedirectResponse
    {
        $this->authorize('update', $debitNote);

        if (! $debitNote->isDebitNote()) {
            abort(404);
        }

        try {
            $this->invoiceService->update($debitNote, $request->validated());
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('purchases.debit-notes.show', $debitNote)
            ->with('success', 'Debit note updated successfully.');
    }

    public function pdf(Invoice $debitNote): Response
    {
        $this->authorize('view', $debitNote);

        $debitNote->load(['lines.account', 'lines.taxCode', 'contact']);
        $business = $debitNote->business;

        $pdf = Pdf::loadView('pdf.invoice', ['invoice' => $debitNote, 'business' => $business]);

        return $pdf->download("{$debitNote->number}.pdf");
    }

    public function destroy(Invoice $debitNote): RedirectResponse
    {
        $this->authorize('delete', $debitNote);

        if (! $debitNote->isDebitNote()) {
            abort(404);
        }

        $debitNote->lines()->delete();
        $debitNote->delete();

        return redirect()->route('purchases.debit-notes.index')
            ->with('success', 'Debit note deleted successfully.');
    }
}
