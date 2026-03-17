<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Contact;
use App\Models\Payment;
use App\Services\Payments\PaymentService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SupplierPaymentController extends Controller
{
    public function __construct(private PaymentService $paymentService) {}

    public function index(Request $request)
    {
        $payments = Payment::query()->payments()->with('contact')->orderByDesc('date')->paginate(25);

        return Inertia::render('payments/supplier-payments/index', ['payments' => $payments, 'filters' => $request->only('search')]);
    }

    public function create()
    {
        return Inertia::render('payments/supplier-payments/create', [
            'suppliers' => Contact::query()->suppliers()->active()->orderBy('name')->get(['id', 'name']),
            'bankAccounts' => Account::query()->bankAccounts()->active()->get(['id', 'code', 'name']),
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
        $payment = $this->paymentService->makePayment($business, $validated);

        return redirect()->route('payments.supplier-payments.show', $payment)->with('success', 'Payment made successfully.');
    }

    public function show(Payment $supplierPayment)
    {
        return Inertia::render('payments/supplier-payments/show', [
            'payment' => $supplierPayment->load(['contact', 'allocations.invoice', 'journalEntry.lines.account', 'bankAccount']),
        ]);
    }
}
