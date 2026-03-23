<?php

namespace App\Services\Banking;

use App\Models\BankTransaction;
use App\Models\Business;
use DomainException;
use Illuminate\Database\Eloquent\Collection;

class BankTransactionService
{
    /**
     * Create a manual bank transaction (not linked to a payment).
     *
     * @param  array{bank_account_id: int, date: string, description: string, amount: float, reference: ?string}  $data
     */
    public function create(Business $business, array $data): BankTransaction
    {
        return BankTransaction::withoutGlobalScopes()->create([
            'business_id' => $business->id,
            'bank_account_id' => $data['bank_account_id'],
            'date' => $data['date'],
            'description' => $data['description'],
            'amount' => $data['amount'],
            'reference' => $data['reference'] ?? null,
        ]);
    }

    /**
     * Update a manual bank transaction's details.
     *
     * @param  array{date?: string, description?: string, amount?: float, reference?: ?string}  $data
     */
    public function update(BankTransaction $transaction, array $data): BankTransaction
    {
        if ($transaction->payment_id || $transaction->journal_entry_id) {
            throw new DomainException('Cannot edit a bank transaction that is linked to a payment or journal entry.');
        }

        $transaction->update($data);

        return $transaction->fresh();
    }

    /**
     * Delete a manual bank transaction.
     */
    public function delete(BankTransaction $transaction): void
    {
        if ($transaction->is_reconciled) {
            throw new DomainException('Cannot delete a reconciled bank transaction.');
        }

        if ($transaction->payment_id) {
            throw new DomainException('Cannot delete a bank transaction linked to a payment.');
        }

        $transaction->delete();
    }

    /**
     * Return unreconciled transactions for a given bank account up to a date.
     *
     * @return Collection<int, BankTransaction>
     */
    public function getUnreconciledFor(int $bankAccountId, string $upToDate): Collection
    {
        return BankTransaction::query()
            ->where('bank_account_id', $bankAccountId)
            ->where('date', '<=', $upToDate)
            ->unreconciled()
            ->orderBy('date')
            ->get();
    }
}
