<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Contact;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\Payments\PaymentService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReceiptController extends Controller
{
    public function __construct(private PaymentService $paymentService) {}

    public function index(Request $request)
    {
        $receipts = Payment::query()
            ->receipts()
            ->with('contact')
            ->orderByDesc('date')
            ->paginate(25);

        return Inertia::render('payments/receipts/index', [
            'receipts' => $receipts,
            'filters' => $request->only('search'),
        ]);
    }

    public function create()
    {
        return Inertia::render('payments/receipts/create', [
            'customers' => Contact::query()->customers()->active()->orderBy('name')->get(['id', 'name']),
            'bankAccounts' => Account::query()->bankAccounts()->active()->get(['id', 'code', 'name']),
            'outstandingInvoices' => Invoice::query()->invoices()->unpaid()->with('contact')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'contact_id' => 'required|exists:contacts,id',
            'date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'bank_account_id' => 'required|exists:accounts,id',
            'reference' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'allocations' => 'nullable|array',
            'allocations.*.invoice_id' => 'required_with:allocations|exists:invoices,id',
            'allocations.*.amount' => 'required_with:allocations|numeric|min:0.01',
        ]);

        $business = $request->user()->currentBusiness();
        $payment = $this->paymentService->receivePayment($business, $validated);

        return redirect()->route('payments.receipts.show', $payment)
            ->with('success', 'Payment received successfully.');
    }

    public function show(Payment $receipt)
    {
        return Inertia::render('payments/receipts/show', [
            'receipt' => $receipt->load(['contact', 'allocations.invoice', 'journalEntry.lines.account', 'bankAccount']),
        ]);
    }
}
