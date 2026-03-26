<?php

namespace App\Services\Accounting;

use App\Domain\Accounting\Enums\AccountSubType;
use App\Domain\Accounting\Enums\AccountType;
use App\Models\Account;
use App\Models\Business;
use App\Models\IntercompanyTransaction;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class IntercompanyService
{
    public function __construct(
        private JournalService $journalService,
    ) {}

    /**
     * Record an inter-company transfer, creating paired journal entries in both businesses.
     *
     * @param  array{source_account_id: int, target_business_id: int, target_account_id: int, amount: float, date: string, description: string, reference?: string}  $data
     */
    public function transfer(Business $sourceBusiness, array $data): IntercompanyTransaction
    {
        return DB::transaction(function () use ($sourceBusiness, $data) {
            $targetBusiness = Business::findOrFail($data['target_business_id']);
            $sourceAccount = Account::withoutGlobalScopes()->findOrFail($data['source_account_id']);
            $targetAccount = Account::withoutGlobalScopes()->findOrFail($data['target_account_id']);
            $amount = (float) $data['amount'];
            $date = Carbon::parse($data['date']);
            $description = $data['description'];

            // Source side: credit the source account (funds leaving)
            $sourceNormal = $sourceAccount->type->normalBalance();
            $sourceJournalEntry = $this->journalService->createAndPost(
                business: $sourceBusiness,
                date: $date,
                lines: [
                    [
                        'account_id' => $sourceAccount->id,
                        'debit' => $sourceNormal === 'credit' ? $amount : 0,
                        'credit' => $sourceNormal === 'debit' ? $amount : 0,
                        'description' => $description,
                    ],
                    [
                        'account_id' => $this->getIntercompanyAccount($sourceBusiness)->id,
                        'debit' => $sourceNormal === 'debit' ? $amount : 0,
                        'credit' => $sourceNormal === 'credit' ? $amount : 0,
                        'description' => "Transfer to {$targetBusiness->name}",
                    ],
                ],
                description: $description,
                sourceType: 'intercompany',
            );

            // Target side: debit the target account (funds arriving)
            $targetNormal = $targetAccount->type->normalBalance();
            $targetJournalEntry = $this->journalService->createAndPost(
                business: $targetBusiness,
                date: $date,
                lines: [
                    [
                        'account_id' => $this->getIntercompanyAccount($targetBusiness)->id,
                        'debit' => $targetNormal === 'credit' ? $amount : 0,
                        'credit' => $targetNormal === 'debit' ? $amount : 0,
                        'description' => "Transfer from {$sourceBusiness->name}",
                    ],
                    [
                        'account_id' => $targetAccount->id,
                        'debit' => $targetNormal === 'debit' ? $amount : 0,
                        'credit' => $targetNormal === 'credit' ? $amount : 0,
                        'description' => $description,
                    ],
                ],
                description: $description,
                sourceType: 'intercompany',
            );

            return IntercompanyTransaction::create([
                'source_business_id' => $sourceBusiness->id,
                'target_business_id' => $targetBusiness->id,
                'source_account_id' => $sourceAccount->id,
                'target_account_id' => $targetAccount->id,
                'amount' => $amount,
                'date' => $date,
                'description' => $description,
                'reference' => $data['reference'] ?? null,
                'source_journal_entry_id' => $sourceJournalEntry->id,
                'target_journal_entry_id' => $targetJournalEntry->id,
                'created_by' => auth()->id(),
            ]);
        });
    }

    /**
     * Get or create the intercompany clearing account for a business.
     */
    private function getIntercompanyAccount(Business $business): Account
    {
        return Account::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->where('sub_type', AccountSubType::INTERCOMPANY->value)
            ->firstOrCreate(
                [
                    'business_id' => $business->id,
                    'sub_type' => AccountSubType::INTERCOMPANY->value,
                ],
                [
                    'code' => '3900',
                    'name' => 'Intercompany Clearing',
                    'type' => AccountType::EQUITY->value,
                    'is_system' => true,
                    'is_active' => true,
                ],
            );
    }

    /**
     * List intercompany transactions for a business (as source or target).
     *
     * @return Collection<int, IntercompanyTransaction>
     */
    public function listForBusiness(Business $business): Collection
    {
        return IntercompanyTransaction::query()
            ->where(function ($q) use ($business) {
                $q->where('source_business_id', $business->id)
                    ->orWhere('target_business_id', $business->id);
            })
            ->with(['sourceBusiness', 'targetBusiness', 'sourceAccount', 'targetAccount'])
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();
    }
}
