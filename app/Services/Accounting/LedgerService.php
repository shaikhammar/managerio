<?php

namespace App\Services\Accounting;

use App\Models\Account;
use App\Models\Business;
use App\Models\JournalEntryLine;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LedgerService
{
    /**
     * Get the balance for a specific account as of a given date.
     * Considers the account's normal balance (debit or credit).
     */
    public function getAccountBalance(Account $account, ?Carbon $asOfDate = null): float
    {
        $query = JournalEntryLine::query()
            ->where('account_id', $account->id)
            ->whereHas('journalEntry', function ($q) use ($asOfDate) {
                $q->withoutGlobalScopes()
                    ->where('is_posted', true);

                if ($asOfDate) {
                    $q->where('date', '<=', $asOfDate);
                }
            });

        $totalDebit = (string) $query->sum('debit');
        $totalCredit = (string) $query->sum('credit');

        // For debit-normal accounts (assets, expenses): balance = debits - credits
        // For credit-normal accounts (liabilities, equity, revenue): balance = credits - debits
        if ($account->type->normalBalance() === 'debit') {
            return (float) bcsub($totalDebit, $totalCredit, 2);
        }

        return (float) bcsub($totalCredit, $totalDebit, 2);
    }

    /**
     * Get all transactions for an account within a date range.
     */
    public function getAccountTransactions(
        Account $account,
        ?Carbon $startDate = null,
        ?Carbon $endDate = null,
    ): Collection {
        return JournalEntryLine::query()
            ->where('account_id', $account->id)
            ->whereHas('journalEntry', function ($q) use ($startDate, $endDate) {
                $q->withoutGlobalScopes()
                    ->where('is_posted', true)
                    ->where('business_id', $account->business_id);

                if ($startDate) {
                    $q->where('date', '>=', $startDate);
                }
                if ($endDate) {
                    $q->where('date', '<=', $endDate);
                }
            })
            ->with(['journalEntry', 'contact'])
            ->orderBy(
                DB::raw('(SELECT date FROM journal_entries WHERE journal_entries.id = journal_entry_lines.journal_entry_id)')
            )
            ->get();
    }

    /**
     * Get the trial balance for a business as of a given date.
     * Returns accounts with their debit and credit balances.
     */
    public function getTrialBalance(Business $business, ?Carbon $asOfDate = null): Collection
    {
        $accounts = Account::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $totals = $this->fetchBatchTotals($accounts->pluck('id'), $business->id, $asOfDate);

        return $accounts->map(function ($account) use ($totals) {
            $row = $totals->get($account->id);
            $totalDebit = (string) ($row?->total_debit ?? '0');
            $totalCredit = (string) ($row?->total_credit ?? '0');
            $balance = (float) bcsub($totalDebit, $totalCredit, 2);

            return [
                'account' => $account,
                'debit' => $balance >= 0 ? abs($balance) : 0,
                'credit' => $balance < 0 ? abs($balance) : 0,
            ];
        })->filter(fn ($item) => $item['debit'] != 0 || $item['credit'] != 0)
            ->values();
    }

    /**
     * Get the general ledger for a business within a date range.
     */
    public function getGeneralLedger(
        Business $business,
        ?Carbon $startDate = null,
        ?Carbon $endDate = null,
    ): Collection {
        $accounts = Account::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        return $accounts->map(function ($account) use ($startDate, $endDate) {
            $transactions = $this->getAccountTransactions($account, $startDate, $endDate);
            $balance = $this->getAccountBalance($account, $endDate);

            return [
                'account' => $account,
                'transactions' => $transactions,
                'closing_balance' => $balance,
            ];
        })->filter(fn ($item) => $item['transactions']->isNotEmpty())
            ->values();
    }

    /**
     * Get the balance of a specific account sub-type for a business.
     */
    public function getSubTypeBalance(
        Business $business,
        string $subType,
        ?Carbon $asOfDate = null,
    ): float {
        $accounts = Account::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->where('sub_type', $subType)
            ->get(['id', 'type']);

        if ($accounts->isEmpty()) {
            return 0.0;
        }

        $totals = $this->fetchBatchTotals($accounts->pluck('id'), $business->id, $asOfDate);

        return (float) $accounts->reduce(function ($carry, $account) use ($totals) {
            $row = $totals->get($account->id);
            $totalDebit = (string) ($row?->total_debit ?? '0');
            $totalCredit = (string) ($row?->total_credit ?? '0');

            $balance = $account->type->normalBalance() === 'debit'
                ? bcsub($totalDebit, $totalCredit, 2)
                : bcsub($totalCredit, $totalDebit, 2);

            return bcadd($carry, $balance, 2);
        }, '0');
    }

    /**
     * Fetch aggregate debit/credit totals for multiple accounts in a single query.
     * Returns a collection keyed by account_id.
     *
     * @param  Collection|array  $accountIds
     */
    public function fetchBatchTotals(
        Collection|array $accountIds,
        int $businessId,
        ?Carbon $asOfDate,
        ?Carbon $startDate = null,
    ): Collection {
        $ids = $accountIds instanceof Collection ? $accountIds->all() : $accountIds;

        if (empty($ids)) {
            return collect();
        }

        return JournalEntryLine::query()
            ->select(
                'journal_entry_lines.account_id',
                DB::raw('SUM(journal_entry_lines.debit) as total_debit'),
                DB::raw('SUM(journal_entry_lines.credit) as total_credit'),
            )
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->whereIn('journal_entry_lines.account_id', $ids)
            ->where('journal_entries.business_id', $businessId)
            ->where('journal_entries.is_posted', true)
            ->when($asOfDate, fn ($q) => $q->where('journal_entries.date', '<=', $asOfDate))
            ->when($startDate, fn ($q) => $q->where('journal_entries.date', '>=', $startDate))
            ->groupBy('journal_entry_lines.account_id')
            ->get()
            ->keyBy('account_id');
    }
}
