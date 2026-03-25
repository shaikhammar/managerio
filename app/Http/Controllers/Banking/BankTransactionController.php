<?php

namespace App\Http\Controllers\Banking;

use App\Http\Controllers\Controller;
use App\Http\Requests\Banking\BankTransactionRequest;
use App\Models\Account;
use App\Models\BankTransaction;
use App\Services\Banking\BankTransactionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BankTransactionController extends Controller
{
    public function __construct(private BankTransactionService $transactionService) {}

    public function index(Request $request): Response
    {
        $transactions = BankTransaction::query()
            ->with(['bankAccount', 'payment.contact'])
            ->when($request->search, fn ($q, $s) => $q->where('description', 'ilike', "%{$s}%"))
            ->when($request->bank_account_id, fn ($q, $id) => $q->where('bank_account_id', $id))
            ->orderByDesc('date')
            ->orderByDesc('created_at')
            ->paginate(25);

        return Inertia::render('banking/transactions/index', [
            'transactions' => $transactions,
            'filters' => $request->only('search', 'bank_account_id'),
            'bankAccounts' => Account::bankAccounts()->active()->get(['id', 'name', 'code']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('banking/transactions/create', [
            'bankAccounts' => Account::bankAccounts()->active()->get(['id', 'name', 'code']),
        ]);
    }

    public function store(BankTransactionRequest $request): RedirectResponse
    {
        $business = $request->user()->currentBusiness();
        $transaction = $this->transactionService->create($business, $request->validated());

        return redirect()->route('banking.transactions.show', $transaction)
            ->with('success', 'Transaction created successfully.');
    }

    public function show(BankTransaction $transaction): Response
    {
        return Inertia::render('banking/transactions/show', [
            'transaction' => $transaction->load(['bankAccount', 'payment.contact', 'journalEntry.lines.account']),
        ]);
    }

    public function edit(BankTransaction $transaction): Response
    {
        return Inertia::render('banking/transactions/edit', [
            'transaction' => $transaction->load('bankAccount'),
            'bankAccounts' => Account::bankAccounts()->active()->get(['id', 'name', 'code']),
        ]);
    }

    public function update(BankTransactionRequest $request, BankTransaction $transaction): RedirectResponse
    {
        $this->transactionService->update($transaction, $request->validated());

        return redirect()->route('banking.transactions.show', $transaction)
            ->with('success', 'Transaction updated successfully.');
    }

    public function destroy(BankTransaction $transaction): RedirectResponse
    {
        $this->transactionService->delete($transaction);

        return redirect()->route('banking.transactions.index')
            ->with('success', 'Transaction deleted successfully.');
    }
}
