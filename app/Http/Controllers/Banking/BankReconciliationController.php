<?php

namespace App\Http\Controllers\Banking;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\BankReconciliation;
use App\Models\BankTransaction;
use App\Services\Accounting\LedgerService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BankReconciliationController extends Controller
{
    public function __construct(private LedgerService $ledger) {}

    public function index(Request $request)
    {
        $reconciliations = BankReconciliation::query()
            ->with('bankAccount')
            ->orderByDesc('statement_date')
            ->paginate(25);

        return Inertia::render('banking/reconciliations/index', [
            'reconciliations' => $reconciliations,
        ]);
    }

    public function create()
    {
        return Inertia::render('banking/reconciliations/create', [
            'bankAccounts' => Account::bankAccounts()->active()->get(['id', 'name', 'code']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'bank_account_id' => 'required|exists:accounts,id',
            'statement_date' => 'required|date',
            'statement_balance' => 'required|numeric',
        ]);

        $account = Account::findOrFail($validated['bank_account_id']);
        
        // Calculate the actual ledger balance as of that date
        $ledgerBalance = $this->ledger->getAccountBalance($account, Carbon::parse($validated['statement_date']));

        $reconciliation = BankReconciliation::create([
            'business_id' => $request->user()->current_business_id,
            'bank_account_id' => $validated['bank_account_id'],
            'statement_date' => $validated['statement_date'],
            'statement_balance' => $validated['statement_balance'],
            'reconciled_balance' => $ledgerBalance,
            'is_completed' => false,
        ]);

        return redirect()->route('banking.reconciliations.show', $reconciliation);
    }

    public function show(BankReconciliation $reconciliation)
    {
        $reconciliation->load('bankAccount');

        // Fetch unreconciled transactions for this account up to the statement date
        $transactions = BankTransaction::query()
            ->where('bank_account_id', $reconciliation->bank_account_id)
            ->where('date', '<=', $reconciliation->statement_date)
            ->where(function ($q) use ($reconciliation) {
                $q->where('is_reconciled', false)
                  ->orWhere('reconciled_at', '>', $reconciliation->completed_at ?? now());
            })
            ->orderBy('date')
            ->get();

        return Inertia::render('banking/reconciliations/show', [
            'reconciliation' => $reconciliation,
            'transactions' => $transactions,
        ]);
    }

    public function update(Request $request, BankReconciliation $reconciliation)
    {
        if ($request->action === 'complete') {
            $reconciliation->update([
                'is_completed' => true,
                'completed_at' => now(),
            ]);

            // Mark matched transactions as reconciled
            if ($request->transaction_ids) {
                BankTransaction::whereIn('id', $request->transaction_ids)
                    ->update([
                        'is_reconciled' => true,
                        'reconciled_at' => now(),
                    ]);
            }

            return redirect()->route('banking.reconciliations.index')
                ->with('success', 'Reconciliation completed successfully.');
        }

        return back();
    }

    public function destroy(BankReconciliation $reconciliation)
    {
        $reconciliation->delete();
        return redirect()->route('banking.reconciliations.index');
    }
}
