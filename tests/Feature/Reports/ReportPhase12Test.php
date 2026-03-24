<?php

use App\Domain\Accounting\Enums\AccountSubType;
use App\Domain\Accounting\Enums\AccountType;
use App\Models\Account;
use App\Models\Business;
use App\Models\User;
use App\Services\Accounting\JournalService;
use App\Services\Accounting\ReportService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

beforeEach(function () {
    $this->business = Business::factory()->create(['currency_code' => 'USD']);
    $this->user = User::factory()->create();
    $this->business->users()->attach($this->user, ['role' => 'owner']);
    Auth::login($this->user);

    $this->reportService = app(ReportService::class);
    $this->journalService = app(JournalService::class);

    $this->bankAccount = Account::factory()->create([
        'business_id' => $this->business->id,
        'type' => AccountType::ASSET,
        'sub_type' => AccountSubType::BANK,
        'code' => '1010',
    ]);

    $this->arAccount = Account::factory()->create([
        'business_id' => $this->business->id,
        'type' => AccountType::ASSET,
        'sub_type' => AccountSubType::ACCOUNTS_RECEIVABLE,
        'code' => '1100',
    ]);

    $this->revenueAccount = Account::factory()->create([
        'business_id' => $this->business->id,
        'type' => AccountType::REVENUE,
        'sub_type' => AccountSubType::SALES_REVENUE,
        'code' => '4000',
    ]);

    $this->equityAccount = Account::factory()->create([
        'business_id' => $this->business->id,
        'type' => AccountType::EQUITY,
        'sub_type' => AccountSubType::OWNER_EQUITY,
        'code' => '3000',
    ]);
});

// ── Account Transactions ─────────────────────────────────────────────────────

it('returns account transactions with opening balance and running balance', function () {
    // Transaction before period (contributes to opening balance)
    $this->journalService->createAndPost(
        business: $this->business,
        date: Carbon::parse('2025-12-31'),
        lines: [
            ['account_id' => $this->bankAccount->id, 'debit' => 1000, 'credit' => 0, 'description' => 'Opening dep'],
            ['account_id' => $this->equityAccount->id, 'debit' => 0, 'credit' => 1000, 'description' => 'Owner capital'],
        ],
        description: 'Opening',
    );

    // Transaction in period
    $this->journalService->createAndPost(
        business: $this->business,
        date: Carbon::parse('2026-01-15'),
        lines: [
            ['account_id' => $this->bankAccount->id, 'debit' => 500, 'credit' => 0, 'description' => 'Cash receipt'],
            ['account_id' => $this->revenueAccount->id, 'debit' => 0, 'credit' => 500, 'description' => 'Revenue'],
        ],
        description: 'Sale',
    );

    $report = $this->reportService->accountTransactions(
        $this->business,
        $this->bankAccount,
        Carbon::parse('2026-01-01'),
        Carbon::parse('2026-01-31'),
    );

    expect($report['opening_balance'])->toBe(1000.0)
        ->and($report['transactions'])->toHaveCount(1)
        ->and($report['transactions'][0]['balance'])->toBe(1500.0)
        ->and($report['closing_balance'])->toBe(1500.0);
});

it('account transactions page loads without account selected', function () {
    $this->actingAs($this->user)
        ->get('/reports/account-transactions')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('reports/account-transactions')
            ->has('accounts')
            ->where('report', null)
        );
});

it('account transactions page loads with account selected', function () {
    $this->actingAs($this->user)
        ->get("/reports/account-transactions?account_id={$this->bankAccount->id}")
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('reports/account-transactions')
            ->has('report')
        );
});

// ── Cash Flow ────────────────────────────────────────────────────────────────

it('cash flow statement page loads', function () {
    $this->actingAs($this->user)
        ->get('/reports/cash-flow')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('reports/cash-flow')
            ->has('report.operating')
            ->has('report.investing')
            ->has('report.financing')
            ->has('report.closing_cash')
        );
});

it('cash flow net income and AR change offset correctly', function () {
    $start = Carbon::parse('2026-01-01');
    $end = Carbon::parse('2026-01-31');

    // Revenue booked to AR (not yet collected — cash effect is zero)
    $this->journalService->createAndPost(
        business: $this->business,
        date: Carbon::parse('2026-01-10'),
        lines: [
            ['account_id' => $this->arAccount->id, 'debit' => 500, 'credit' => 0, 'description' => 'AR'],
            ['account_id' => $this->revenueAccount->id, 'debit' => 0, 'credit' => 500, 'description' => 'Revenue'],
        ],
        description: 'Invoice',
    );

    $report = $this->reportService->cashFlow($this->business, $start, $end);

    expect($report['operating']['net_income'])->toBe(500.0)
        ->and($report['operating']['change_in_receivables'])->toBe(-500.0)
        ->and($report['operating']['total'])->toBe(0.0);
});

// ── Equity Statement ─────────────────────────────────────────────────────────

it('equity statement page loads', function () {
    $this->actingAs($this->user)
        ->get('/reports/equity-statement')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('reports/equity-statement')
            ->has('report.accounts')
            ->has('report.net_income')
            ->has('report.total_opening')
            ->has('report.total_closing')
        );
});

it('equity statement shows owner equity movement', function () {
    $start = Carbon::parse('2026-01-01');
    $end = Carbon::parse('2026-01-31');

    $this->journalService->createAndPost(
        business: $this->business,
        date: Carbon::parse('2026-01-05'),
        lines: [
            ['account_id' => $this->bankAccount->id, 'debit' => 10000, 'credit' => 0, 'description' => 'Bank'],
            ['account_id' => $this->equityAccount->id, 'debit' => 0, 'credit' => 10000, 'description' => 'Capital'],
        ],
        description: 'Capital injection',
    );

    $report = $this->reportService->equityStatement($this->business, $start, $end);

    $equityRow = collect($report['accounts'])->firstWhere('id', $this->equityAccount->id);

    expect($equityRow)->not->toBeNull()
        ->and($equityRow['movement'])->toBe(10000.0)
        ->and($equityRow['closing_balance'])->toBe(10000.0);
});
