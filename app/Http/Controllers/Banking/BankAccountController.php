<?php

namespace App\Http\Controllers\Banking;

use App\Domain\Accounting\Enums\AccountSubType;
use App\Domain\Accounting\Enums\AccountType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Banking\BankAccountRequest;
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
                'description' => $account->description,
                'balance' => $this->ledger->getAccountBalance($account, Carbon::now()),
            ]);

        return Inertia::render('banking/accounts/index', ['bankAccounts' => $bankAccounts]);
    }

    public function create()
    {
        return Inertia::render('banking/accounts/create');
    }

    public function store(BankAccountRequest $request)
    {
        $this->authorize('create', Account::class);

        $validated = $request->validated();

        $description = collect([
            $validated['bank_name'] ?? null,
            ! empty($validated['account_number']) ? 'Acct #'.$validated['account_number'] : null,
        ])->filter()->implode(' · ');

        Account::create([
            'code' => $validated['code'],
            'name' => $validated['name'],
            'type' => AccountType::ASSET,
            'sub_type' => AccountSubType::BANK,
            'description' => $description ?: null,
            'is_active' => true,
        ]);

        return redirect()->route('banking.accounts.index')
            ->with('success', 'Bank account created successfully.');
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
