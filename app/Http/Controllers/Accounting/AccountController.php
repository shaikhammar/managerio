<?php

namespace App\Http\Controllers\Accounting;

use App\Domain\Accounting\Enums\AccountType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Accounting\AccountRequest;
use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Services\Accounting\LedgerService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AccountController extends Controller
{
    public function index(Request $request)
    {
        $accounts = Account::query()
            ->with('parent')
            ->when($request->type, fn ($q, $type) => $q->where('type', $type))
            ->when($request->search, fn ($q, $s) => $q->where('name', 'ilike', "%{$s}%")->orWhere('code', 'ilike', "%{$s}%"))
            ->orderBy('code')
            ->paginate(50);

        return Inertia::render('accounting/accounts/index', [
            'accounts' => $accounts,
            'filters' => $request->only('type', 'search'),
            'accountTypes' => collect(AccountType::cases())->map(fn ($t) => ['value' => $t->value, 'label' => $t->label()]),
        ]);
    }

    public function create()
    {
        return Inertia::render('accounting/accounts/create', [
            'accountTypes' => collect(AccountType::cases())->map(fn ($t) => ['value' => $t->value, 'label' => $t->label()]),
            'parentAccounts' => Account::query()->active()->orderBy('code')->get(['id', 'code', 'name', 'type']),
        ]);
    }

    public function store(AccountRequest $request)
    {
        Account::create($request->validated());

        return redirect()->route('accounting.accounts.index')
            ->with('success', 'Account created successfully.');
    }

    public function show(Account $account)
    {
        $ledgerService = app(LedgerService::class);

        $transactions = JournalEntryLine::query()
            ->where('account_id', $account->id)
            ->with('journalEntry')
            ->whereHas('journalEntry', fn ($q) => $q->where('is_posted', true))
            ->orderByDesc(
                JournalEntry::select('date')
                    ->whereColumn('journal_entries.id', 'journal_entry_lines.journal_entry_id')
            )
            ->paginate(50);

        return Inertia::render('accounting/accounts/show', [
            'account' => $account->load('parent', 'children'),
            'transactions' => $transactions,
            'balance' => $ledgerService->getAccountBalance($account, Carbon::now()),
        ]);
    }

    public function edit(Account $account)
    {
        if ($account->is_system) {
            return redirect()->route('accounting.accounts.index')
                ->with('error', 'System accounts cannot be edited.');
        }

        return Inertia::render('accounting/accounts/create', [
            'account' => $account,
            'accountTypes' => collect(AccountType::cases())->map(fn ($t) => ['value' => $t->value, 'label' => $t->label()]),
            'parentAccounts' => Account::query()->active()->where('id', '!=', $account->id)->orderBy('code')->get(['id', 'code', 'name', 'type']),
        ]);
    }

    public function update(AccountRequest $request, Account $account)
    {
        if ($account->is_system) {
            return back()->with('error', 'System accounts cannot be edited.');
        }

        $account->update($request->validated());

        return redirect()->route('accounting.accounts.index')
            ->with('success', 'Account updated successfully.');
    }

    public function destroy(Account $account)
    {
        if ($account->is_system) {
            return back()->with('error', 'System accounts cannot be deleted.');
        }

        if ($account->journalEntryLines()->exists()) {
            return back()->with('error', 'Cannot delete account with existing transactions.');
        }

        $account->delete();

        return redirect()->route('accounting.accounts.index')
            ->with('success', 'Account deleted successfully.');
    }
}
