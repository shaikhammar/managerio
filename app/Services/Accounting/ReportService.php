<?php

namespace App\Services\Accounting;

use App\Domain\Accounting\Enums\AccountType;
use App\Models\Account;
use App\Models\Business;
use App\Models\Invoice;
use App\Models\JournalEntryLine;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ReportService
{
    public function __construct(
        private LedgerService $ledger,
    ) {}

    /**
     * Profit and Loss report for a date range.
     * Revenue - Expenses = Net Profit
     */
    public function profitAndLoss(Business $business, Carbon $startDate, Carbon $endDate): array
    {
        $revenueAccounts = $this->getAccountBalancesOfType($business, AccountType::REVENUE, $startDate, $endDate);
        $expenseAccounts = $this->getAccountBalancesOfType($business, AccountType::EXPENSE, $startDate, $endDate);

        $totalRevenue = $revenueAccounts->sum('balance');
        $totalExpenses = $expenseAccounts->sum('balance');
        $netProfit = $totalRevenue - $totalExpenses;

        return [
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'revenue' => [
                'accounts' => $revenueAccounts,
                'total' => $totalRevenue,
            ],
            'expenses' => [
                'accounts' => $expenseAccounts,
                'total' => $totalExpenses,
            ],
            'net_profit' => $netProfit,
        ];
    }

    /**
     * Balance Sheet report as of a given date.
     * Assets = Liabilities + Equity
     */
    public function balanceSheet(Business $business, Carbon $asOfDate): array
    {
        $assetAccounts = $this->getAccountBalancesOfType($business, AccountType::ASSET, null, $asOfDate);
        $liabilityAccounts = $this->getAccountBalancesOfType($business, AccountType::LIABILITY, null, $asOfDate);
        $equityAccounts = $this->getAccountBalancesOfType($business, AccountType::EQUITY, null, $asOfDate);

        $totalAssets = $assetAccounts->sum('balance');
        $totalLiabilities = $liabilityAccounts->sum('balance');
        $totalEquity = $equityAccounts->sum('balance');

        // Retained earnings calculation (accumulated P&L to date)
        $revenueBalance = $this->getTotalTypeBalance($business, AccountType::REVENUE, null, $asOfDate);
        $expenseBalance = $this->getTotalTypeBalance($business, AccountType::EXPENSE, null, $asOfDate);
        $retainedEarnings = $revenueBalance - $expenseBalance;

        return [
            'as_of' => $asOfDate->toDateString(),
            'assets' => [
                'accounts' => $assetAccounts,
                'total' => $totalAssets,
            ],
            'liabilities' => [
                'accounts' => $liabilityAccounts,
                'total' => $totalLiabilities,
            ],
            'equity' => [
                'accounts' => $equityAccounts,
                'total' => $totalEquity,
                'retained_earnings' => $retainedEarnings,
            ],
            'total_liabilities_and_equity' => $totalLiabilities + $totalEquity + $retainedEarnings,
        ];
    }

    /**
     * Aged Receivables report.
     * Groups outstanding invoices by age buckets (current, 30, 60, 90+ days).
     */
    public function agedReceivables(Business $business, Carbon $asOfDate): array
    {
        $invoices = Invoice::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->where('type', 'invoice')
            ->where('balance_due', '>', 0)
            ->whereNotIn('status', ['void', 'cancelled'])
            ->with('contact')
            ->get();

        return $this->buildAgedReport($invoices, $asOfDate);
    }

    /**
     * Aged Payables report.
     */
    public function agedPayables(Business $business, Carbon $asOfDate): array
    {
        $invoices = Invoice::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->where('type', 'purchase_invoice')
            ->where('balance_due', '>', 0)
            ->whereNotIn('status', ['void', 'cancelled'])
            ->with('contact')
            ->get();

        return $this->buildAgedReport($invoices, $asOfDate);
    }

    // ── Private Helpers ─────────────────────────────────────────

    private function getAccountBalancesOfType(
        Business $business,
        AccountType $type,
        ?Carbon $startDate,
        Carbon $endDate,
    ): Collection {
        $accounts = Account::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->where('type', $type)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        return $accounts->map(function ($account) use ($startDate, $endDate) {
            $query = JournalEntryLine::query()
                ->where('account_id', $account->id)
                ->whereHas('journalEntry', function ($q) use ($account, $startDate, $endDate) {
                    $q->withoutGlobalScopes()
                        ->where('business_id', $account->business_id)
                        ->where('is_posted', true)
                        ->where('date', '<=', $endDate);

                    if ($startDate) {
                        $q->where('date', '>=', $startDate);
                    }
                });

            $totalDebit = (float) $query->sum('debit');
            $totalCredit = (float) $query->sum('credit');

            $balance = $account->type->normalBalance() === 'debit'
                ? $totalDebit - $totalCredit
                : $totalCredit - $totalDebit;

            return [
                'id' => $account->id,
                'code' => $account->code,
                'name' => $account->name,
                'balance' => $balance,
            ];
        })->filter(fn ($item) => $item['balance'] != 0)
            ->values();
    }

    private function getTotalTypeBalance(
        Business $business,
        AccountType $type,
        ?Carbon $startDate,
        Carbon $endDate,
    ): float {
        return $this->getAccountBalancesOfType($business, $type, $startDate, $endDate)
            ->sum('balance');
    }

    private function buildAgedReport(Collection $invoices, Carbon $asOfDate): array
    {
        $buckets = [
            'current' => ['label' => 'Current', 'min' => 0, 'max' => 30, 'total' => 0, 'items' => []],
            '31_60' => ['label' => '31-60 Days', 'min' => 31, 'max' => 60, 'total' => 0, 'items' => []],
            '61_90' => ['label' => '61-90 Days', 'min' => 61, 'max' => 90, 'total' => 0, 'items' => []],
            '90_plus' => ['label' => '90+ Days', 'min' => 91, 'max' => PHP_INT_MAX, 'total' => 0, 'items' => []],
        ];

        foreach ($invoices as $invoice) {
            $daysOld = $asOfDate->diffInDays($invoice->due_date ?? $invoice->date, false);
            $daysOverdue = max(0, (int) $daysOld);

            $bucket = match (true) {
                $daysOverdue <= 30 => 'current',
                $daysOverdue <= 60 => '31_60',
                $daysOverdue <= 90 => '61_90',
                default => '90_plus',
            };

            $buckets[$bucket]['total'] += (float) $invoice->balance_due;
            $buckets[$bucket]['items'][] = [
                'invoice_id' => $invoice->id,
                'number' => $invoice->number,
                'contact' => $invoice->contact?->name,
                'date' => $invoice->date->toDateString(),
                'due_date' => $invoice->due_date?->toDateString(),
                'total' => (float) $invoice->total,
                'balance_due' => (float) $invoice->balance_due,
                'days_overdue' => $daysOverdue,
            ];
        }

        return [
            'as_of' => $asOfDate->toDateString(),
            'buckets' => $buckets,
            'grand_total' => collect($buckets)->sum('total'),
        ];
    }
}
