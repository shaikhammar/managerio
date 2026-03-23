<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payments\PaymentRequest;
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

    public function store(PaymentRequest $request)
    {
        $this->authorize('create', Payment::class);

        $business = $request->user()->currentBusiness();
        $payment = $this->paymentService->receivePayment($business, $request->validated());

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
