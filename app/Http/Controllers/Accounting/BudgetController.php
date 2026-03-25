<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Http\Requests\Accounting\BudgetRequest;
use App\Models\Account;
use App\Services\Accounting\BudgetService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BudgetController extends Controller
{
    public function __construct(
        private BudgetService $budgetService,
    ) {}

    public function index(Request $request)
    {
        $year = (int) ($request->year ?? now()->year);
        $business = $request->user()->currentBusiness();

        $report = $this->budgetService->getBudgetVsActual($business, $year);

        return Inertia::render('accounting/budgets/index', [
            'report' => $report,
            'year' => $year,
        ]);
    }

    public function edit(Request $request)
    {
        $year = (int) ($request->year ?? now()->year);
        $business = $request->user()->currentBusiness();

        $accounts = Account::query()
            ->whereIn('type', ['revenue', 'expense'])
            ->active()
            ->orderBy('type')
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'type']);

        $existingBudgets = $this->budgetService->getBudgetForYear($business, $year);

        return Inertia::render('accounting/budgets/edit', [
            'accounts' => $accounts,
            'existingBudgets' => $existingBudgets,
            'year' => $year,
        ]);
    }

    public function save(BudgetRequest $request)
    {
        $validated = $request->validated();
        $business = $request->user()->currentBusiness();

        $this->budgetService->saveBudget(
            business: $business,
            year: $validated['year'],
            entries: $validated['entries'],
        );

        return back()->with('success', 'Budget saved.');
    }
}
