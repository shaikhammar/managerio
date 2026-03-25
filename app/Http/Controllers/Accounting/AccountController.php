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
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AccountController extends Controller
{
    public function __construct(private LedgerService $ledger) {}

    public function index(Request $request): Response
    {
        $business = $request->user()->currentBusiness();

        $accounts = Account::query()
            ->with('parent')
            ->when($request->type, fn ($q, $type) => $q->where('type', $type))
            ->when($request->search, fn ($q, $s) => $q->where('name', 'ilike', "%{$s}%")->orWhere('code', 'ilike', "%{$s}%"))
            ->orderBy('code')
            ->paginate(50);

        $rawBalances = $this->ledger->fetchBatchTotals($accounts->pluck('id'), $business->id, null);

        // Attach balance to each account using its normal balance side
        $accounts->getCollection()->transform(function (Account $account) use ($rawBalances) {
            $row = $rawBalances->get($account->id);
            $debit = (string) ($row?->total_debit ?? '0');
            $credit = (string) ($row?->total_credit ?? '0');
            $account->balance = (float) ($account->type->normalBalance() === 'debit'
                ? bcsub($debit, $credit, 2)
                : bcsub($credit, $debit, 2));

            return $account;
        });

        return Inertia::render('accounting/accounts/index', [
            'accounts' => $accounts,
            'filters' => $request->only('type', 'search'),
            'accountTypes' => collect(AccountType::cases())->map(fn ($t) => ['value' => $t->value, 'label' => $t->label()]),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('accounting/accounts/create', [
            'accountTypes' => collect(AccountType::cases())->map(fn ($t) => ['value' => $t->value, 'label' => $t->label()]),
            'parentAccounts' => Account::query()->active()->orderBy('code')->get(['id', 'code', 'name', 'type']),
        ]);
    }

    public function store(AccountRequest $request): RedirectResponse
    {
        $this->authorize('create', Account::class);

        Account::create($request->validated());

        return redirect()->route('accounting.accounts.index')
            ->with('success', 'Account created successfully.');
    }

    public function show(Account $account): Response
    {

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
            'balance' => $this->ledger->getAccountBalance($account, Carbon::now()),
        ]);
    }

    public function edit(Account $account): Response|RedirectResponse
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

    public function update(AccountRequest $request, Account $account): RedirectResponse
    {
        $this->authorize('update', $account);

        if ($account->is_system) {
            return back()->with('error', 'System accounts cannot be edited.');
        }

        $account->update($request->validated());

        return redirect()->route('accounting.accounts.index')
            ->with('success', 'Account updated successfully.');
    }

    public function destroy(Account $account): RedirectResponse
    {
        $this->authorize('delete', $account);

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
