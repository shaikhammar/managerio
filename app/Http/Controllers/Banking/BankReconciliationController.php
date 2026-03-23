<?php

namespace App\Http\Controllers\Banking;

use App\Http\Controllers\Controller;
use App\Http\Requests\Banking\BankReconciliationRequest;
use App\Models\Account;
use App\Models\BankReconciliation;
use App\Services\Banking\ReconciliationService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BankReconciliationController extends Controller
{
    public function __construct(private ReconciliationService $reconciliationService) {}

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

    public function store(BankReconciliationRequest $request)
    {
        $business = $request->user()->currentBusiness();
        $reconciliation = $this->reconciliationService->start($business, $request->validated());

        return redirect()->route('banking.reconciliations.show', $reconciliation);
    }

    public function show(BankReconciliation $reconciliation)
    {
        return Inertia::render('banking/reconciliations/show', [
            'reconciliation' => $reconciliation->load('bankAccount'),
            'transactions' => $this->reconciliationService->getTransactionsFor($reconciliation),
        ]);
    }

    public function update(Request $request, BankReconciliation $reconciliation)
    {
        if ($request->action === 'complete') {
            $this->reconciliationService->complete($reconciliation, $request->transaction_ids ?? []);

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
