<?php

namespace App\Http\Controllers\Accounting;

use App\Domain\Accounting\Enums\AccountSubType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Accounting\OpeningBalanceRequest;
use App\Models\Account;
use App\Models\JournalEntry;
use App\Services\Accounting\JournalService;
use Inertia\Inertia;

class OpeningBalanceController extends Controller
{
    public function __construct(
        private JournalService $journalService,
    ) {}

    public function create()
    {
        $accounts = Account::query()
            ->active()
            ->orderBy('type')
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'type', 'sub_type']);

        $existingEntry = JournalEntry::query()
            ->where('source_type', 'opening_balance')
            ->latest('date')
            ->first();

        return Inertia::render('accounting/opening-balances/create', [
            'accounts' => $accounts,
            'existingEntry' => $existingEntry,
        ]);
    }

    public function store(OpeningBalanceRequest $request)
    {
        $validated = $request->validated();
        $business = $request->user()->currentBusiness();

        $accountIds = collect($validated['lines'])->pluck('account_id');
        $accounts = Account::query()
            ->whereIn('id', $accountIds)
            ->get()
            ->keyBy('id');

        // Build balanced journal lines using each account's normal balance direction.
        // An "Opening Balance Equity" account absorbs the net difference.
        $obEquityAccount = Account::query()
            ->where('sub_type', AccountSubType::OWNER_EQUITY->value)
            ->where('is_system', true)
            ->first();

        $lines = [];
        $netDebit = 0;
        $netCredit = 0;

        foreach ($validated['lines'] as $line) {
            $account = $accounts[$line['account_id']];
            $balance = abs((float) $line['balance']);

            if ($balance <= 0) {
                continue;
            }

            if ($account->type->normalBalance() === 'debit') {
                $lines[] = [
                    'account_id' => $account->id,
                    'description' => 'Opening balance',
                    'debit' => $balance,
                    'credit' => 0,
                ];
                $netDebit += $balance;
            } else {
                $lines[] = [
                    'account_id' => $account->id,
                    'description' => 'Opening balance',
                    'debit' => 0,
                    'credit' => $balance,
                ];
                $netCredit += $balance;
            }
        }

        // Balance via Opening Balance Equity
        $difference = round($netDebit - $netCredit, 2);

        if ($difference > 0 && $obEquityAccount) {
            $lines[] = [
                'account_id' => $obEquityAccount->id,
                'description' => 'Opening balance equity offset',
                'debit' => 0,
                'credit' => $difference,
            ];
        } elseif ($difference < 0 && $obEquityAccount) {
            $lines[] = [
                'account_id' => $obEquityAccount->id,
                'description' => 'Opening balance equity offset',
                'debit' => abs($difference),
                'credit' => 0,
            ];
        }

        $entry = $this->journalService->createAndPost(
            business: $business,
            date: $validated['date'],
            lines: $lines,
            description: $validated['description'] ?? 'Opening balances',
            sourceType: 'opening_balance',
        );

        return redirect()->route('accounting.journal-entries.show', $entry)
            ->with('success', 'Opening balances posted successfully.');
    }
}
