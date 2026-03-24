<?php

namespace App\Services\Accounting;

use App\Domain\Accounting\Enums\AccountSubType;
use App\Domain\Accounting\Enums\AccountType;
use App\Models\Account;
use App\Models\Business;
use App\Models\Invoice;
use App\Models\JournalEntryLine;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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
     * Account Transactions report — all transactions for a single account
     * within a date range, with opening balance and running balance per line.
     */
    public function accountTransactions(Business $business, Account $account, Carbon $startDate, Carbon $endDate): array
    {
        // Opening balance: sum of all posted transactions before start date
        $openingBalance = $this->ledger->getAccountBalance($account, $startDate->copy()->subDay());

        $lines = JournalEntryLine::query()
            ->where('account_id', $account->id)
            ->whereHas('journalEntry', function ($q) use ($account, $startDate, $endDate) {
                $q->withoutGlobalScopes()
                    ->where('business_id', $account->business_id)
                    ->where('is_posted', true)
                    ->whereBetween('date', [$startDate, $endDate]);
            })
            ->with(['journalEntry', 'contact'])
            ->orderBy(
                DB::raw('(SELECT date FROM journal_entries WHERE journal_entries.id = journal_entry_lines.journal_entry_id)')
            )
            ->orderBy('id')
            ->get();

        $isDebitNormal = $account->type->normalBalance() === 'debit';
        $runningBalance = $openingBalance;
        $transactions = [];

        foreach ($lines as $line) {
            $movement = $isDebitNormal
                ? (float) $line->debit - (float) $line->credit
                : (float) $line->credit - (float) $line->debit;

            $runningBalance += $movement;

            $transactions[] = [
                'id' => $line->id,
                'date' => $line->journalEntry->date->toDateString(),
                'entry_number' => $line->journalEntry->entry_number,
                'description' => $line->description ?? $line->journalEntry->description,
                'contact' => $line->contact?->name,
                'debit' => (float) $line->debit,
                'credit' => (float) $line->credit,
                'balance' => round($runningBalance, 2),
            ];
        }

        return [
            'account' => [
                'id' => $account->id,
                'code' => $account->code,
                'name' => $account->name,
                'type' => $account->type->value,
            ],
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'opening_balance' => round($openingBalance, 2),
            'closing_balance' => round($runningBalance, 2),
            'transactions' => $transactions,
        ];
    }

    /**
     * Cash Flow Statement — indirect method.
     * Operating: Net income + non-cash adjustments + working capital changes.
     * Investing: Changes in fixed assets.
     * Financing: Changes in equity accounts (owner contributions/withdrawals).
     */
    public function cashFlow(Business $business, Carbon $startDate, Carbon $endDate): array
    {
        // ── Operating Activities ─────────────────────────────────────────────
        $netIncome = $this->getTotalTypeBalance($business, AccountType::REVENUE, $startDate, $endDate)
            - $this->getTotalTypeBalance($business, AccountType::EXPENSE, $startDate, $endDate);

        // Add back depreciation (non-cash expense)
        $depreciation = $this->getSubTypeMovement($business, AccountSubType::DEPRECIATION, $startDate, $endDate);

        // Working capital changes — decrease in assets = cash in, increase in liabilities = cash in
        $arStart = $this->ledger->getSubTypeBalance($business, AccountSubType::ACCOUNTS_RECEIVABLE->value, $startDate->copy()->subDay());
        $arEnd = $this->ledger->getSubTypeBalance($business, AccountSubType::ACCOUNTS_RECEIVABLE->value, $endDate);
        $arChange = $arStart - $arEnd; // decrease = positive (cash collected)

        $apStart = $this->ledger->getSubTypeBalance($business, AccountSubType::ACCOUNTS_PAYABLE->value, $startDate->copy()->subDay());
        $apEnd = $this->ledger->getSubTypeBalance($business, AccountSubType::ACCOUNTS_PAYABLE->value, $endDate);
        $apChange = $apEnd - $apStart; // increase in AP = cash conserved

        $taxRecStart = $this->ledger->getSubTypeBalance($business, AccountSubType::TAX_RECEIVABLE->value, $startDate->copy()->subDay());
        $taxRecEnd = $this->ledger->getSubTypeBalance($business, AccountSubType::TAX_RECEIVABLE->value, $endDate);
        $taxRecChange = $taxRecStart - $taxRecEnd;

        $taxPayStart = $this->ledger->getSubTypeBalance($business, AccountSubType::TAX_PAYABLE->value, $startDate->copy()->subDay());
        $taxPayEnd = $this->ledger->getSubTypeBalance($business, AccountSubType::TAX_PAYABLE->value, $endDate);
        $taxPayChange = $taxPayEnd - $taxPayStart;

        $operatingTotal = $netIncome + $depreciation + $arChange + $apChange + $taxRecChange + $taxPayChange;

        // ── Investing Activities ──────────────────────────────────────────────
        $fixedAssetStart = $this->ledger->getSubTypeBalance($business, AccountSubType::FIXED_ASSET->value, $startDate->copy()->subDay());
        $fixedAssetEnd = $this->ledger->getSubTypeBalance($business, AccountSubType::FIXED_ASSET->value, $endDate);
        $fixedAssetChange = $fixedAssetStart - $fixedAssetEnd; // decrease = cash from disposal

        $investingTotal = $fixedAssetChange;

        // ── Financing Activities ──────────────────────────────────────────────
        $equityStart = $this->ledger->getSubTypeBalance($business, AccountSubType::OWNER_EQUITY->value, $startDate->copy()->subDay());
        $equityEnd = $this->ledger->getSubTypeBalance($business, AccountSubType::OWNER_EQUITY->value, $endDate);
        $equityChange = $equityEnd - $equityStart; // increase = owner contribution

        $financingTotal = $equityChange;

        // ── Net Change in Cash ────────────────────────────────────────────────
        $netChange = $operatingTotal + $investingTotal + $financingTotal;

        $openingCash = $this->ledger->getSubTypeBalance($business, AccountSubType::BANK->value, $startDate->copy()->subDay())
            + $this->ledger->getSubTypeBalance($business, AccountSubType::CASH->value, $startDate->copy()->subDay());

        $closingCash = $openingCash + $netChange;

        return [
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'operating' => [
                'net_income' => round($netIncome, 2),
                'depreciation' => round($depreciation, 2),
                'change_in_receivables' => round($arChange, 2),
                'change_in_payables' => round($apChange, 2),
                'change_in_tax_receivable' => round($taxRecChange, 2),
                'change_in_tax_payable' => round($taxPayChange, 2),
                'total' => round($operatingTotal, 2),
            ],
            'investing' => [
                'change_in_fixed_assets' => round($fixedAssetChange, 2),
                'total' => round($investingTotal, 2),
            ],
            'financing' => [
                'change_in_equity' => round($equityChange, 2),
                'total' => round($financingTotal, 2),
            ],
            'net_change' => round($netChange, 2),
            'opening_cash' => round($openingCash, 2),
            'closing_cash' => round($closingCash, 2),
        ];
    }

    /**
     * Statement of Changes in Equity for a period.
     * Shows opening balance, net income, owner movements, and closing balance
     * for each equity account.
     */
    public function equityStatement(Business $business, Carbon $startDate, Carbon $endDate): array
    {
        $equityAccounts = Account::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->where('type', AccountType::EQUITY)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $netIncome = $this->getTotalTypeBalance($business, AccountType::REVENUE, $startDate, $endDate)
            - $this->getTotalTypeBalance($business, AccountType::EXPENSE, $startDate, $endDate);

        $accounts = $equityAccounts->map(function ($account) use ($startDate, $endDate) {
            $openingBalance = $this->ledger->getAccountBalance($account, $startDate->copy()->subDay());
            $closingBalance = $this->ledger->getAccountBalance($account, $endDate);
            $movement = $closingBalance - $openingBalance;

            return [
                'id' => $account->id,
                'code' => $account->code,
                'name' => $account->name,
                'opening_balance' => round($openingBalance, 2),
                'movement' => round($movement, 2),
                'closing_balance' => round($closingBalance, 2),
            ];
        })->values();

        $totalOpening = $accounts->sum('opening_balance');
        $totalClosing = $accounts->sum('closing_balance');

        return [
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'accounts' => $accounts,
            'net_income' => round($netIncome, 2),
            'total_opening' => round($totalOpening, 2),
            'total_closing' => round($totalClosing + $netIncome, 2),
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

    /**
     * Get the net movement (debit - credit) for all accounts of a given sub-type in a period.
     * Used for depreciation addback in cash flow.
     */
    private function getSubTypeMovement(Business $business, AccountSubType $subType, Carbon $startDate, Carbon $endDate): float
    {
        $accounts = Account::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->where('sub_type', $subType)
            ->get();

        $total = 0.0;
        foreach ($accounts as $account) {
            $lines = JournalEntryLine::query()
                ->where('account_id', $account->id)
                ->whereHas('journalEntry', function ($q) use ($account, $startDate, $endDate) {
                    $q->withoutGlobalScopes()
                        ->where('business_id', $account->business_id)
                        ->where('is_posted', true)
                        ->whereBetween('date', [$startDate, $endDate]);
                });

            $total += (float) $lines->sum('debit') - (float) $lines->sum('credit');
        }

        return $total;
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
