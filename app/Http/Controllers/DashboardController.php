<?php

namespace App\Http\Controllers;

use App\Domain\Accounting\Enums\AccountSubType;
use App\Models\Account;
use App\Models\Business;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\Accounting\LedgerService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private LedgerService $ledger,
    ) {}

    public function __invoke(Request $request): Response
    {
        $businessId = session('current_business_id');
        $business = Business::find($businessId);

        if (! $business) {
            return Inertia::render('business/index', [
                'businesses' => $request->user()->businesses()->get(),
            ]);
        }

        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();

        // Bank balances — single batch query
        $bankAccountModels = Account::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->where('sub_type', AccountSubType::BANK)
            ->get();

        $bankTotals = $this->ledger->fetchBatchTotals($bankAccountModels->pluck('id'), $business->id, $now);

        $bankAccounts = $bankAccountModels->map(fn ($account) => [
            'id' => $account->id,
            'name' => $account->name,
            // BANK accounts are ASSET (debit-normal): balance = debits - credits
            'balance' => (float) ($bankTotals->get($account->id)?->total_debit ?? 0)
                       - (float) ($bankTotals->get($account->id)?->total_credit ?? 0),
        ]);

        // Receivables & Payables
        $receivables = $this->ledger->getSubTypeBalance($business, AccountSubType::ACCOUNTS_RECEIVABLE->value, $now);
        $payables = $this->ledger->getSubTypeBalance($business, AccountSubType::ACCOUNTS_PAYABLE->value, $now);

        // Revenue & Expenses this month — one batch query per type with date range
        $revenueAccountIds = Account::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->where('type', 'revenue')
            ->pluck('id');

        $revenueTotals = $this->ledger->fetchBatchTotals($revenueAccountIds, $business->id, $endOfMonth, $startOfMonth);
        // Revenue is credit-normal: monthly revenue = sum(credits) - sum(debits) across all revenue accounts
        $monthlyRevenue = $revenueTotals->sum(
            fn ($row) => (float) $row->total_credit - (float) $row->total_debit
        );

        $expenseAccountIds = Account::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->where('type', 'expense')
            ->pluck('id');

        $expenseTotals = $this->ledger->fetchBatchTotals($expenseAccountIds, $business->id, $endOfMonth, $startOfMonth);
        // Expenses are debit-normal: monthly expenses = sum(debits) - sum(credits) across all expense accounts
        $monthlyExpenses = $expenseTotals->sum(
            fn ($row) => (float) $row->total_debit - (float) $row->total_credit
        );

        // Recent invoices
        $recentInvoices = Invoice::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->where('type', 'invoice')
            ->with('contact')
            ->orderByDesc('date')
            ->limit(5)
            ->get()
            ->map(fn ($inv) => [
                'id' => $inv->id,
                'number' => $inv->number,
                'contact' => $inv->contact?->name,
                'date' => $inv->date->toDateString(),
                'total' => (float) $inv->total,
                'balance_due' => (float) $inv->balance_due,
                'status' => $inv->status->value,
            ]);

        // Recent payments
        $recentPayments = Payment::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->with('contact')
            ->orderByDesc('date')
            ->limit(5)
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'number' => $p->number,
                'contact' => $p->contact?->name,
                'date' => $p->date->toDateString(),
                'amount' => (float) $p->amount,
                'type' => $p->type->value,
            ]);

        return Inertia::render('dashboard', [
            'bankAccounts' => $bankAccounts,
            'receivables' => $receivables,
            'payables' => $payables,
            'monthlyRevenue' => $monthlyRevenue,
            'monthlyExpenses' => $monthlyExpenses,
            'recentInvoices' => $recentInvoices,
            'recentPayments' => $recentPayments,
        ]);
    }
}
