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

        $totalDebit = (float) $query->sum('debit');
        $totalCredit = (float) $query->sum('credit');

        // For debit-normal accounts (assets, expenses): balance = debits - credits
        // For credit-normal accounts (liabilities, equity, revenue): balance = credits - debits
        if ($account->type->normalBalance() === 'debit') {
            return $totalDebit - $totalCredit;
        }

        return $totalCredit - $totalDebit;
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

        return $accounts->map(function ($account) use ($asOfDate) {
            $query = JournalEntryLine::query()
                ->where('account_id', $account->id)
                ->whereHas('journalEntry', function ($q) use ($account, $asOfDate) {
                    $q->withoutGlobalScopes()
                        ->where('business_id', $account->business_id)
                        ->where('is_posted', true);

                    if ($asOfDate) {
                        $q->where('date', '<=', $asOfDate);
                    }
                });

            $totalDebit = (float) $query->sum('debit');
            $totalCredit = (float) $query->sum('credit');
            $balance = $totalDebit - $totalCredit;

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
            ->get();

        return $accounts->sum(fn ($account) => $this->getAccountBalance($account, $asOfDate));
    }
}
