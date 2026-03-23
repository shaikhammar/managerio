<?php

namespace App\Services\Banking;

use App\Models\Account;
use App\Models\BankReconciliation;
use App\Models\BankTransaction;
use App\Models\Business;
use App\Services\Accounting\LedgerService;
use Carbon\Carbon;
use DomainException;
use Illuminate\Database\Eloquent\Collection;

class ReconciliationService
{
    public function __construct(private LedgerService $ledger) {}

    /**
     * Start a new bank reconciliation.
     *
     * @param  array{bank_account_id: int, statement_date: string, statement_balance: float}  $data
     */
    public function start(Business $business, array $data): BankReconciliation
    {
        $account = Account::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->findOrFail($data['bank_account_id']);

        $ledgerBalance = $this->ledger->getAccountBalance($account, Carbon::parse($data['statement_date']));

        return BankReconciliation::withoutGlobalScopes()->create([
            'business_id' => $business->id,
            'bank_account_id' => $data['bank_account_id'],
            'statement_date' => $data['statement_date'],
            'statement_balance' => $data['statement_balance'],
            'reconciled_balance' => $ledgerBalance,
            'is_completed' => false,
        ]);
    }

    /**
     * Complete a reconciliation and mark the given transactions as reconciled.
     *
     * @param  array<int>  $transactionIds
     */
    public function complete(BankReconciliation $reconciliation, array $transactionIds): void
    {
        if ($reconciliation->is_completed) {
            throw new DomainException('Reconciliation is already completed.');
        }

        $reconciliation->update([
            'is_completed' => true,
            'completed_at' => now(),
        ]);

        if (! empty($transactionIds)) {
            BankTransaction::withoutGlobalScopes()
                ->whereIn('id', $transactionIds)
                ->update([
                    'is_reconciled' => true,
                    'reconciled_at' => now(),
                ]);
        }
    }

    /**
     * Get transactions eligible for reconciliation against this reconciliation.
     *
     * @return Collection<int, BankTransaction>
     */
    public function getTransactionsFor(BankReconciliation $reconciliation): Collection
    {
        return BankTransaction::query()
            ->where('bank_account_id', $reconciliation->bank_account_id)
            ->where('date', '<=', $reconciliation->statement_date)
            ->where(function ($q) use ($reconciliation) {
                $q->where('is_reconciled', false)
                    ->orWhere('reconciled_at', '>', $reconciliation->completed_at ?? now());
            })
            ->orderBy('date')
            ->get();
    }
}
