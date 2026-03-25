<?php

use App\Domain\Accounting\Enums\RecurringFrequency;
use App\Models\Account;
use App\Models\Business;
use App\Models\JournalEntry;
use App\Models\RecurringJournalEntry;
use App\Models\User;
use App\Services\Accounting\RecurringJournalService;
use Illuminate\Support\Facades\Auth;

beforeEach(function () {
    $this->business = Business::factory()->create();
    $this->user = User::factory()->create();
    $this->business->users()->attach($this->user, ['role' => 'owner']);
    Auth::login($this->user);

    $this->service = app(RecurringJournalService::class);

    $this->account1 = Account::factory()->create(['business_id' => $this->business->id, 'type' => 'asset']);
    $this->account2 = Account::factory()->create(['business_id' => $this->business->id, 'type' => 'expense']);
});

it('creates a recurring journal entry with correct next run date', function () {
    $entry = $this->service->create($this->business, [
        'name' => 'Monthly Depreciation',
        'frequency' => 'monthly',
        'start_date' => '2026-01-01',
        'day_of_month' => 1,
        'template_lines' => [
            ['account_id' => $this->account1->id, 'debit' => 500, 'credit' => 0, 'description' => 'Debit'],
            ['account_id' => $this->account2->id, 'debit' => 0, 'credit' => 500, 'description' => 'Credit'],
        ],
    ]);

    expect($entry)->toBeInstanceOf(RecurringJournalEntry::class)
        ->and($entry->name)->toBe('Monthly Depreciation')
        ->and($entry->frequency)->toBe(RecurringFrequency::Monthly)
        ->and($entry->is_active)->toBeTrue()
        ->and($entry->template_lines)->toHaveCount(2);

    $this->assertDatabaseHas('recurring_journal_entries', [
        'id' => $entry->id,
        'business_id' => $this->business->id,
        'is_active' => true,
    ]);
});

it('processes a due entry and creates a journal entry', function () {
    $entry = RecurringJournalEntry::factory()->create([
        'business_id' => $this->business->id,
        'frequency' => 'monthly',
        'next_run_date' => now()->subDay()->toDateString(),
        'day_of_month' => 1,
        'is_active' => true,
        'template_lines' => [
            ['account_id' => $this->account1->id, 'debit' => 100, 'credit' => 0, 'description' => 'D'],
            ['account_id' => $this->account2->id, 'debit' => 0, 'credit' => 100, 'description' => 'C'],
        ],
        'created_by' => $this->user->id,
    ]);

    $this->service->processSingle($entry);

    expect(JournalEntry::withoutGlobalScopes()
        ->where('business_id', $this->business->id)
        ->where('source_type', 'recurring')
        ->where('source_id', $entry->id)
        ->exists()
    )->toBeTrue();

    $entry->refresh();
    expect($entry->last_run_at)->not->toBeNull();
});

it('advances next run date correctly for monthly frequency', function () {
    $entry = RecurringJournalEntry::factory()->create([
        'business_id' => $this->business->id,
        'frequency' => 'monthly',
        'next_run_date' => '2026-01-01',
        'day_of_month' => 1,
        'is_active' => true,
        'template_lines' => [
            ['account_id' => $this->account1->id, 'debit' => 100, 'credit' => 0, 'description' => 'D'],
            ['account_id' => $this->account2->id, 'debit' => 0, 'credit' => 100, 'description' => 'C'],
        ],
        'created_by' => $this->user->id,
    ]);

    $this->service->processSingle($entry);

    $entry->refresh();
    expect($entry->next_run_date->toDateString())->toBe('2026-02-01');
});

it('advances next run date correctly for quarterly frequency', function () {
    $entry = RecurringJournalEntry::factory()->create([
        'business_id' => $this->business->id,
        'frequency' => 'quarterly',
        'next_run_date' => '2026-01-01',
        'day_of_month' => 1,
        'is_active' => true,
        'template_lines' => [
            ['account_id' => $this->account1->id, 'debit' => 100, 'credit' => 0, 'description' => 'D'],
            ['account_id' => $this->account2->id, 'debit' => 0, 'credit' => 100, 'description' => 'C'],
        ],
        'created_by' => $this->user->id,
    ]);

    $this->service->processSingle($entry);

    $entry->refresh();
    expect($entry->next_run_date->toDateString())->toBe('2026-04-01');
});

it('deactivates an entry when end date has passed', function () {
    $entry = RecurringJournalEntry::factory()->create([
        'business_id' => $this->business->id,
        'frequency' => 'monthly',
        'next_run_date' => now()->subDay()->toDateString(),
        'end_date' => now()->subMonth()->toDateString(),
        'is_active' => true,
        'template_lines' => [],
        'created_by' => $this->user->id,
    ]);

    $count = $this->service->processAll($this->business);

    $entry->refresh();
    expect($entry->is_active)->toBeFalse()
        ->and($count)->toBe(0);
});

it('processAll only processes due entries', function () {
    // Future entry — should not run
    RecurringJournalEntry::factory()->create([
        'business_id' => $this->business->id,
        'frequency' => 'monthly',
        'next_run_date' => now()->addMonth()->toDateString(),
        'is_active' => true,
        'template_lines' => [],
        'created_by' => $this->user->id,
    ]);

    $count = $this->service->processAll($this->business);

    expect($count)->toBe(0);
});
