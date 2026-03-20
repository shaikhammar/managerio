<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\QuoteRequest;
use App\Models\Account;
use App\Models\Contact;
use App\Models\Invoice;
use App\Models\TaxCode;
use App\Services\Sales\QuoteService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class QuoteController extends Controller
{
    public function __construct(
        private QuoteService $quoteService,
    ) {}

    public function index(Request $request)
    {
        $quotes = Invoice::query()
            ->quotes()
            ->with('contact')
            ->when($request->search, fn ($q, $s) => $q->where('number', 'ilike', "%{$s}%"))
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->orderByDesc('date')
            ->paginate(25);

        return Inertia::render('sales/quotes/index', [
            'quotes' => $quotes,
            'filters' => $request->only('search', 'status'),
        ]);
    }

    public function create()
    {
        return Inertia::render('sales/quotes/create', [
            'customers' => Contact::query()->customers()->active()->orderBy('name')->get(['id', 'name']),
            'accounts' => Account::query()->active()->whereIn('type', ['revenue', 'expense'])->orderBy('code')->get(['id', 'code', 'name', 'type']),
            'taxCodes' => TaxCode::query()->active()->orderBy('name')->get(['id', 'name', 'rate']),
        ]);
    }

    public function store(QuoteRequest $request)
    {
        $business = $request->user()->currentBusiness();
        $quote = $this->quoteService->create($business, $request->validated());

        return redirect()->route('sales.quotes.show', $quote)
            ->with('success', 'Quote created successfully.');
    }

    public function show(Invoice $quote)
    {
        if (! $quote->isQuote()) {
            abort(404);
        }

        return Inertia::render('sales/quotes/show', [
            'quote' => $quote->load(['lines.account', 'lines.taxCode', 'contact']),
        ]);
    }

    public function edit(Invoice $quote)
    {
        if (! $quote->isQuote()) {
            abort(404);
        }

        return Inertia::render('sales/quotes/create', [
            'quote' => $quote->load('lines'),
            'customers' => Contact::query()->customers()->active()->orderBy('name')->get(['id', 'name']),
            'accounts' => Account::query()->active()->whereIn('type', ['revenue', 'expense'])->orderBy('code')->get(['id', 'code', 'name', 'type']),
            'taxCodes' => TaxCode::query()->active()->orderBy('name')->get(['id', 'name', 'rate']),
        ]);
    }

    public function update(QuoteRequest $request, Invoice $quote)
    {
        $this->quoteService->update($quote, $request->validated());

        return redirect()->route('sales.quotes.show', $quote)
            ->with('success', 'Quote updated successfully.');
    }

    public function convert(Invoice $quote)
    {
        $invoice = $this->quoteService->convertToInvoice($quote);

        return redirect()->route('sales.invoices.show', $invoice)
            ->with('success', "Quote #{$quote->number} converted to Invoice #{$invoice->number}.");
    }

    public function destroy(Invoice $quote)
    {
        if (! $quote->isQuote()) {
            abort(404);
        }

        $quote->lines()->delete();
        $quote->delete();

        return redirect()->route('sales.quotes.index')
            ->with('success', 'Quote deleted successfully.');
    }
}
