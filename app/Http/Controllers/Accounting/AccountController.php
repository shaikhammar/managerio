<?php

namespace App\Http\Controllers\Accounting;

use App\Domain\Accounting\Enums\AccountType;
use App\Http\Controllers\Controller;
use App\Models\Account;
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

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:20',
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:'.implode(',', array_column(AccountType::cases(), 'value')),
            'sub_type' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:accounts,id',
        ]);

        Account::create($validated);

        return redirect()->route('accounting.accounts.index')
            ->with('success', 'Account created successfully.');
    }

    public function show(Account $account)
    {
        return Inertia::render('accounting/accounts/show', [
            'account' => $account->load('parent', 'children'),
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

    public function update(Request $request, Account $account)
    {
        if ($account->is_system) {
            return back()->with('error', 'System accounts cannot be edited.');
        }

        $validated = $request->validate([
            'code' => 'required|string|max:20',
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:'.implode(',', array_column(AccountType::cases(), 'value')),
            'sub_type' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:accounts,id',
            'is_active' => 'boolean',
        ]);

        $account->update($validated);

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
