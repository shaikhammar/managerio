<?php

use App\Models\Account;
use App\Models\Business;
use App\Models\IntercompanyTransaction;
use App\Models\JournalEntry;
use App\Models\User;
use App\Services\Accounting\IntercompanyService;
use Illuminate\Support\Facades\Auth;

beforeEach(function () {
    $this->user = User::factory()->create();

    $this->sourceBusiness = Business::factory()->create();
    $this->targetBusiness = Business::factory()->create();

    $this->sourceBusiness->users()->attach($this->user, ['role' => 'owner']);
    $this->targetBusiness->users()->attach($this->user, ['role' => 'owner']);

    Auth::login($this->user);
    session(['current_business_id' => $this->sourceBusiness->id]);

    $this->service = app(IntercompanyService::class);

    $this->sourceAccount = Account::factory()->create([
        'business_id' => $this->sourceBusiness->id,
        'type' => 'asset',
        'sub_type' => 'bank',
    ]);

    $this->targetAccount = Account::factory()->create([
        'business_id' => $this->targetBusiness->id,
        'type' => 'asset',
        'sub_type' => 'bank',
    ]);
});

it('creates an intercompany transaction record', function () {
    $transaction = $this->service->transfer($this->sourceBusiness, [
        'source_account_id' => $this->sourceAccount->id,
        'target_business_id' => $this->targetBusiness->id,
        'target_account_id' => $this->targetAccount->id,
        'amount' => 5000,
        'date' => '2026-01-15',
        'description' => 'Management fee',
    ]);

    expect($transaction)->toBeInstanceOf(IntercompanyTransaction::class)
        ->and($transaction->source_business_id)->toBe($this->sourceBusiness->id)
        ->and($transaction->target_business_id)->toBe($this->targetBusiness->id)
        ->and((float) $transaction->amount)->toBe(5000.0);

    $this->assertDatabaseHas('intercompany_transactions', [
        'source_business_id' => $this->sourceBusiness->id,
        'target_business_id' => $this->targetBusiness->id,
        'description' => 'Management fee',
    ]);
});

it('creates paired journal entries in both businesses', function () {
    $transaction = $this->service->transfer($this->sourceBusiness, [
        'source_account_id' => $this->sourceAccount->id,
        'target_business_id' => $this->targetBusiness->id,
        'target_account_id' => $this->targetAccount->id,
        'amount' => 2000,
        'date' => '2026-01-15',
        'description' => 'Intercompany loan',
    ]);

    expect(JournalEntry::withoutGlobalScopes()
        ->where('business_id', $this->sourceBusiness->id)
        ->where('source_type', 'intercompany')
        ->where('id', $transaction->source_journal_entry_id)
        ->exists()
    )->toBeTrue();

    expect(JournalEntry::withoutGlobalScopes()
        ->where('business_id', $this->targetBusiness->id)
        ->where('source_type', 'intercompany')
        ->where('id', $transaction->target_journal_entry_id)
        ->exists()
    )->toBeTrue();
});

it('auto-creates intercompany clearing accounts when missing', function () {
    $this->service->transfer($this->sourceBusiness, [
        'source_account_id' => $this->sourceAccount->id,
        'target_business_id' => $this->targetBusiness->id,
        'target_account_id' => $this->targetAccount->id,
        'amount' => 1000,
        'date' => '2026-01-15',
        'description' => 'Test',
    ]);

    $this->assertDatabaseHas('accounts', [
        'business_id' => $this->sourceBusiness->id,
        'sub_type' => 'intercompany',
        'is_system' => true,
    ]);

    $this->assertDatabaseHas('accounts', [
        'business_id' => $this->targetBusiness->id,
        'sub_type' => 'intercompany',
        'is_system' => true,
    ]);
});

it('lists transactions for the source business', function () {
    $this->service->transfer($this->sourceBusiness, [
        'source_account_id' => $this->sourceAccount->id,
        'target_business_id' => $this->targetBusiness->id,
        'target_account_id' => $this->targetAccount->id,
        'amount' => 500,
        'date' => '2026-01-15',
        'description' => 'Transfer A',
    ]);

    $transactions = $this->service->listForBusiness($this->sourceBusiness);

    expect($transactions)->toHaveCount(1)
        ->and($transactions->first()->description)->toBe('Transfer A');
});

it('lists transactions for the target business too', function () {
    $this->service->transfer($this->sourceBusiness, [
        'source_account_id' => $this->sourceAccount->id,
        'target_business_id' => $this->targetBusiness->id,
        'target_account_id' => $this->targetAccount->id,
        'amount' => 300,
        'date' => '2026-01-15',
        'description' => 'Transfer B',
    ]);

    $transactions = $this->service->listForBusiness($this->targetBusiness);

    expect($transactions)->toHaveCount(1)
        ->and($transactions->first()->description)->toBe('Transfer B');
});
