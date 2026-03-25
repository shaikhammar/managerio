<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payments\PaymentRequest;
use App\Models\Account;
use App\Models\Contact;
use App\Models\Payment;
use App\Services\Payments\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SupplierPaymentController extends Controller
{
    public function __construct(private PaymentService $paymentService) {}

    public function index(Request $request): Response
    {
        $payments = Payment::query()->payments()->with('contact')->orderByDesc('date')->paginate(25);

        return Inertia::render('payments/supplier-payments/index', ['payments' => $payments, 'filters' => $request->only('search')]);
    }

    public function create(): Response
    {
        return Inertia::render('payments/supplier-payments/create', [
            'suppliers' => Contact::query()->suppliers()->active()->orderBy('name')->get(['id', 'name']),
            'bankAccounts' => Account::query()->bankAccounts()->active()->get(['id', 'code', 'name']),
        ]);
    }

    public function store(PaymentRequest $request): RedirectResponse
    {
        $this->authorize('create', Payment::class);

        $business = $request->user()->currentBusiness();
        $payment = $this->paymentService->makePayment($business, $request->validated());

        return redirect()->route('payments.supplier-payments.show', $payment)->with('success', 'Payment made successfully.');
    }

    public function show(Payment $supplierPayment): Response
    {
        return Inertia::render('payments/supplier-payments/show', [
            'payment' => $supplierPayment->load(['contact', 'allocations.invoice', 'journalEntry.lines.account', 'bankAccount']),
        ]);
    }
}
