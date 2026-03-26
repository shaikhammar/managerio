<?php

namespace App\Services\Accounting;

use App\Domain\Accounting\Enums\AccountType;
use App\Models\Account;
use App\Models\AccountBudget;
use App\Models\Business;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BudgetService
{
    public function __construct(
        private LedgerService $ledger,
    ) {}

    /**
     * Upsert budget amounts for a specific year.
     * Each entry must include account_id, month (1-12), and amount.
     *
     * @param  array<int, array{account_id: int, month: int, amount: float}>  $entries
     */
    public function saveBudget(Business $business, int $year, array $entries): void
    {
        DB::transaction(function () use ($business, $year, $entries) {
            foreach ($entries as $entry) {
                AccountBudget::withoutGlobalScopes()->updateOrCreate(
                    [
                        'business_id' => $business->id,
                        'account_id' => $entry['account_id'],
                        'year' => $year,
                        'month' => $entry['month'],
                    ],
                    ['amount' => $entry['amount']],
                );
            }
        });
    }

    /**
     * Get budget vs actual for a given year (monthly breakdown).
     *
     * @return array{year: int, accounts: array<int, array{account: Account, months: array<int, array{budgeted: float, actual: float, variance: float}>, total_budgeted: float, total_actual: float, total_variance: float}>}
     */
    public function getBudgetVsActual(Business $business, int $year): array
    {
        $accounts = Account::query()
            ->whereIn('type', [AccountType::REVENUE->value, AccountType::EXPENSE->value])
            ->active()
            ->orderBy('type')
            ->orderBy('code')
            ->get();

        $budgets = AccountBudget::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->where('year', $year)
            ->whereNotNull('month')
            ->get()
            ->groupBy('account_id')
            ->map(fn ($rows) => $rows->keyBy('month'));

        $rows = $accounts->map(function (Account $account) use ($budgets, $year) {
            $months = collect(range(1, 12))->mapWithKeys(function (int $month) use ($account, $budgets, $year) {
                $startDate = Carbon::create($year, $month, 1)->startOfMonth();
                $endDate = $startDate->copy()->endOfMonth();

                $budgeted = (float) ($budgets[$account->id][$month]->amount ?? 0);
                $actual = $this->ledger->getAccountBalance($account, $endDate)
                    - $this->ledger->getAccountBalance($account, $startDate->copy()->subDay());

                return [$month => [
                    'budgeted' => $budgeted,
                    'actual' => round($actual, 2),
                    'variance' => round($budgeted - $actual, 2),
                ]];
            });

            return [
                'account' => $account,
                'months' => $months,
                'total_budgeted' => $months->sum('budgeted'),
                'total_actual' => $months->sum('actual'),
                'total_variance' => $months->sum('variance'),
            ];
        });

        return [
            'year' => $year,
            'accounts' => $rows->values()->toArray(),
        ];
    }

    /**
     * Get the existing budget rows for a given year to pre-fill the budget input form.
     *
     * @return Collection<int, AccountBudget>
     */
    public function getBudgetForYear(Business $business, int $year): Collection
    {
        return AccountBudget::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->where('year', $year)
            ->whereNotNull('month')
            ->get();
    }
}
