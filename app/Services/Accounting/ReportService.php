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

        $totalRevenue = $revenueAccounts->reduce(fn ($carry, $item) => bcadd($carry, (string) $item['balance'], 2), '0');
        $totalExpenses = $expenseAccounts->reduce(fn ($carry, $item) => bcadd($carry, (string) $item['balance'], 2), '0');
        $netProfit = bcsub($totalRevenue, $totalExpenses, 2);

        return [
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'revenue' => [
                'accounts' => $revenueAccounts,
                'total' => (float) $totalRevenue,
            ],
            'expenses' => [
                'accounts' => $expenseAccounts,
                'total' => (float) $totalExpenses,
            ],
            'net_profit' => (float) $netProfit,
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

        $totalAssets = $assetAccounts->reduce(fn ($carry, $item) => bcadd($carry, (string) $item['balance'], 2), '0');
        $totalLiabilities = $liabilityAccounts->reduce(fn ($carry, $item) => bcadd($carry, (string) $item['balance'], 2), '0');
        $totalEquity = $equityAccounts->reduce(fn ($carry, $item) => bcadd($carry, (string) $item['balance'], 2), '0');

        // Retained earnings calculation (accumulated P&L to date)
        $revenueBalance = $this->getTotalTypeBalance($business, AccountType::REVENUE, null, $asOfDate);
        $expenseBalance = $this->getTotalTypeBalance($business, AccountType::EXPENSE, null, $asOfDate);
        $retainedEarnings = bcsub($revenueBalance, $expenseBalance, 2);

        return [
            'as_of' => $asOfDate->toDateString(),
            'assets' => [
                'accounts' => $assetAccounts,
                'total' => (float) $totalAssets,
            ],
            'liabilities' => [
                'accounts' => $liabilityAccounts,
                'total' => (float) $totalLiabilities,
            ],
            'equity' => [
                'accounts' => $equityAccounts,
                'total' => (float) $totalEquity,
                'retained_earnings' => (float) $retainedEarnings,
            ],
            'total_liabilities_and_equity' => (float) bcadd(bcadd($totalLiabilities, $totalEquity, 2), $retainedEarnings, 2),
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
        $runningBalance = (string) $openingBalance;
        $transactions = [];

        foreach ($lines as $line) {
            $movement = $isDebitNormal
                ? bcsub((string) $line->debit, (string) $line->credit, 2)
                : bcsub((string) $line->credit, (string) $line->debit, 2);

            $runningBalance = bcadd($runningBalance, $movement, 2);

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
            'opening_balance' => (float) $openingBalance,
            'closing_balance' => (float) $runningBalance,
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
        $netIncome = bcsub(
            $this->getTotalTypeBalance($business, AccountType::REVENUE, $startDate, $endDate),
            $this->getTotalTypeBalance($business, AccountType::EXPENSE, $startDate, $endDate),
            2
        );

        // Add back depreciation (non-cash expense)
        $depreciation = (string) $this->getSubTypeMovement($business, AccountSubType::DEPRECIATION, $startDate, $endDate);

        // Working capital changes — decrease in assets = cash in, increase in liabilities = cash in
        $arStart = (string) $this->ledger->getSubTypeBalance($business, AccountSubType::ACCOUNTS_RECEIVABLE->value, $startDate->copy()->subDay());
        $arEnd = (string) $this->ledger->getSubTypeBalance($business, AccountSubType::ACCOUNTS_RECEIVABLE->value, $endDate);
        $arChange = bcsub($arStart, $arEnd, 2); // decrease = positive (cash collected)

        $apStart = (string) $this->ledger->getSubTypeBalance($business, AccountSubType::ACCOUNTS_PAYABLE->value, $startDate->copy()->subDay());
        $apEnd = (string) $this->ledger->getSubTypeBalance($business, AccountSubType::ACCOUNTS_PAYABLE->value, $endDate);
        $apChange = bcsub($apEnd, $apStart, 2); // increase in AP = cash conserved

        $taxRecStart = (string) $this->ledger->getSubTypeBalance($business, AccountSubType::TAX_RECEIVABLE->value, $startDate->copy()->subDay());
        $taxRecEnd = (string) $this->ledger->getSubTypeBalance($business, AccountSubType::TAX_RECEIVABLE->value, $endDate);
        $taxRecChange = bcsub($taxRecStart, $taxRecEnd, 2);

        $taxPayStart = (string) $this->ledger->getSubTypeBalance($business, AccountSubType::TAX_PAYABLE->value, $startDate->copy()->subDay());
        $taxPayEnd = (string) $this->ledger->getSubTypeBalance($business, AccountSubType::TAX_PAYABLE->value, $endDate);
        $taxPayChange = bcsub($taxPayEnd, $taxPayStart, 2);

        $operatingTotal = bcadd(bcadd(bcadd(bcadd(bcadd($netIncome, $depreciation, 2), $arChange, 2), $apChange, 2), $taxRecChange, 2), $taxPayChange, 2);

        // ── Investing Activities ──────────────────────────────────────────────
        $fixedAssetStart = (string) $this->ledger->getSubTypeBalance($business, AccountSubType::FIXED_ASSET->value, $startDate->copy()->subDay());
        $fixedAssetEnd = (string) $this->ledger->getSubTypeBalance($business, AccountSubType::FIXED_ASSET->value, $endDate);
        $fixedAssetChange = bcsub($fixedAssetStart, $fixedAssetEnd, 2); // decrease = cash from disposal

        $investingTotal = $fixedAssetChange;

        // ── Financing Activities ──────────────────────────────────────────────
        $equityStart = (string) $this->ledger->getSubTypeBalance($business, AccountSubType::OWNER_EQUITY->value, $startDate->copy()->subDay());
        $equityEnd = (string) $this->ledger->getSubTypeBalance($business, AccountSubType::OWNER_EQUITY->value, $endDate);
        $equityChange = bcsub($equityEnd, $equityStart, 2); // increase = owner contribution

        $financingTotal = $equityChange;

        // ── Net Change in Cash ────────────────────────────────────────────────
        $netChange = bcadd(bcadd($operatingTotal, $investingTotal, 2), $financingTotal, 2);

        $openingCash = bcadd(
            (string) $this->ledger->getSubTypeBalance($business, AccountSubType::BANK->value, $startDate->copy()->subDay()),
            (string) $this->ledger->getSubTypeBalance($business, AccountSubType::CASH->value, $startDate->copy()->subDay()),
            2
        );

        $closingCash = bcadd($openingCash, $netChange, 2);

        return [
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'operating' => [
                'net_income' => (float) $netIncome,
                'depreciation' => (float) $depreciation,
                'change_in_receivables' => (float) $arChange,
                'change_in_payables' => (float) $apChange,
                'change_in_tax_receivable' => (float) $taxRecChange,
                'change_in_tax_payable' => (float) $taxPayChange,
                'total' => (float) $operatingTotal,
            ],
            'investing' => [
                'change_in_fixed_assets' => (float) $fixedAssetChange,
                'total' => (float) $investingTotal,
            ],
            'financing' => [
                'change_in_equity' => (float) $equityChange,
                'total' => (float) $financingTotal,
            ],
            'net_change' => (float) $netChange,
            'opening_cash' => (float) $openingCash,
            'closing_cash' => (float) $closingCash,
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

        $netIncome = bcsub(
            $this->getTotalTypeBalance($business, AccountType::REVENUE, $startDate, $endDate),
            $this->getTotalTypeBalance($business, AccountType::EXPENSE, $startDate, $endDate),
            2
        );

        $accountIds = $equityAccounts->pluck('id');
        $openingTotals = $this->ledger->fetchBatchTotals($accountIds, $business->id, $startDate->copy()->subDay());
        $closingTotals = $this->ledger->fetchBatchTotals($accountIds, $business->id, $endDate);

        $accounts = $equityAccounts->map(function ($account) use ($openingTotals, $closingTotals) {
            $isDebitNormal = $account->type->normalBalance() === 'debit';

            $calcBalance = function ($row) use ($isDebitNormal): string {
                $d = (string) ($row?->total_debit ?? '0');
                $c = (string) ($row?->total_credit ?? '0');

                return $isDebitNormal ? bcsub($d, $c, 2) : bcsub($c, $d, 2);
            };

            $openingBalance = $calcBalance($openingTotals->get($account->id));
            $closingBalance = $calcBalance($closingTotals->get($account->id));

            return [
                'id' => $account->id,
                'code' => $account->code,
                'name' => $account->name,
                'opening_balance' => (float) $openingBalance,
                'movement' => (float) bcsub($closingBalance, $openingBalance, 2),
                'closing_balance' => (float) $closingBalance,
            ];
        })->values();

        $totalOpening = $accounts->reduce(fn ($carry, $item) => bcadd($carry, (string) $item['opening_balance'], 2), '0');
        $totalClosing = $accounts->reduce(fn ($carry, $item) => bcadd($carry, (string) $item['closing_balance'], 2), '0');

        return [
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'accounts' => $accounts,
            'net_income' => (float) $netIncome,
            'total_opening' => (float) $totalOpening,
            'total_closing' => (float) bcadd($totalClosing, $netIncome, 2),
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

        if ($accounts->isEmpty()) {
            return collect();
        }

        $totals = $this->ledger->fetchBatchTotals(
            $accounts->pluck('id'),
            $business->id,
            $endDate,
            $startDate,
        );

        return $accounts->map(function ($account) use ($totals) {
            $row = $totals->get($account->id);
            $totalDebit = (string) ($row?->total_debit ?? '0');
            $totalCredit = (string) ($row?->total_credit ?? '0');
            $balance = $account->type->normalBalance() === 'debit'
                ? bcsub($totalDebit, $totalCredit, 2)
                : bcsub($totalCredit, $totalDebit, 2);

            return [
                'id' => $account->id,
                'code' => $account->code,
                'name' => $account->name,
                'balance' => (float) $balance,
            ];
        })->filter(fn ($item) => $item['balance'] != 0)
            ->values();
    }

    private function getTotalTypeBalance(
        Business $business,
        AccountType $type,
        ?Carbon $startDate,
        Carbon $endDate,
    ): string {
        return $this->getAccountBalancesOfType($business, $type, $startDate, $endDate)
            ->reduce(fn ($carry, $item) => bcadd($carry, (string) $item['balance'], 2), '0');
    }

    /**
     * Get the net movement (debit - credit) for all accounts of a given sub-type in a period.
     * Used for depreciation addback in cash flow.
     */
    private function getSubTypeMovement(Business $business, AccountSubType $subType, Carbon $startDate, Carbon $endDate): float
    {
        $accountIds = Account::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->where('sub_type', $subType)
            ->pluck('id');

        if ($accountIds->isEmpty()) {
            return 0.0;
        }

        $row = JournalEntryLine::query()
            ->select(
                DB::raw('SUM(journal_entry_lines.debit) as total_debit'),
                DB::raw('SUM(journal_entry_lines.credit) as total_credit'),
            )
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->whereIn('journal_entry_lines.account_id', $accountIds)
            ->where('journal_entries.business_id', $business->id)
            ->where('journal_entries.is_posted', true)
            ->whereBetween('journal_entries.date', [$startDate, $endDate])
            ->first();

        return (float) bcsub((string) ($row?->total_debit ?? '0'), (string) ($row?->total_credit ?? '0'), 2);
    }

    private function buildAgedReport(Collection $invoices, Carbon $asOfDate): array
    {
        $buckets = [
            'current' => ['label' => 'Current', 'min' => 0, 'max' => 30, 'total' => '0', 'items' => []],
            '31_60' => ['label' => '31-60 Days', 'min' => 31, 'max' => 60, 'total' => '0', 'items' => []],
            '61_90' => ['label' => '61-90 Days', 'min' => 61, 'max' => 90, 'total' => '0', 'items' => []],
            '90_plus' => ['label' => '90+ Days', 'min' => 91, 'max' => PHP_INT_MAX, 'total' => '0', 'items' => []],
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

            $buckets[$bucket]['total'] = bcadd($buckets[$bucket]['total'], (string) $invoice->balance_due, 2);
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

        $grandTotal = collect($buckets)->reduce(fn ($carry, $b) => bcadd($carry, $b['total'], 2), '0');

        $buckets = array_map(function ($bucket) {
            $bucket['total'] = (float) $bucket['total'];

            return $bucket;
        }, $buckets);

        return [
            'as_of' => $asOfDate->toDateString(),
            'buckets' => $buckets,
            'grand_total' => (float) $grandTotal,
        ];
    }
}
