<?php

namespace App\Http\Controllers\Banking;

use App\Http\Controllers\Controller;
use App\Models\BankTransaction;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BankTransactionController extends Controller
{
    public function index(Request $request)
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
            'bankAccounts' => \App\Models\Account::bankAccounts()->active()->get(['id', 'name', 'code']),
        ]);
    }

    public function create()
    {
        return Inertia::render('banking/transactions/create');
    }

    public function store(Request $request)
    {
        return back();
    }

    public function show(BankTransaction $transaction)
    {
        return Inertia::render('banking/transactions/show');
    }

    public function edit(BankTransaction $transaction)
    {
        return Inertia::render('banking/transactions/edit');
    }

    public function update(Request $request, BankTransaction $transaction)
    {
        return back();
    }

    public function destroy(BankTransaction $transaction)
    {
        return back();
    }
}
