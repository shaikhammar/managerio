<?php

namespace App\Http\Controllers\Banking;

use App\Domain\Accounting\Enums\AccountSubType;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\BankTransaction;
use App\Services\Accounting\LedgerService;
use Carbon\Carbon;
use Inertia\Inertia;

class BankAccountController extends Controller
{
    public function __construct(private LedgerService $ledger) {}

    public function index()
    {
        $bankAccounts = Account::query()
            ->where('sub_type', AccountSubType::BANK)
            ->active()
            ->get()
            ->map(fn ($account) => [
                'id' => $account->id,
                'code' => $account->code,
                'name' => $account->name,
                'balance' => $this->ledger->getAccountBalance($account, Carbon::now()),
            ]);

        return Inertia::render('banking/accounts/index', ['bankAccounts' => $bankAccounts]);
    }

    public function show(Account $account)
    {
        $transactions = BankTransaction::query()
            ->where('bank_account_id', $account->id)
            ->orderByDesc('date')
            ->paginate(25);

        return Inertia::render('banking/accounts/show', [
            'account' => $account,
            'balance' => $this->ledger->getAccountBalance($account, Carbon::now()),
            'transactions' => $transactions,
        ]);
    }
}
