<?php

use App\Models\Account;
use App\Models\RecurringJournalEntry;
use App\Models\User;
use App\Services\Business\BusinessSetupService;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->business = app(BusinessSetupService::class)->createBusiness($this->user, [
        'name' => 'Test Business',
        'currency_code' => 'USD',
        'country' => 'US',
    ]);

    $this->actingAs($this->user);
    session(['current_business_id' => $this->business->id]);

    $this->account1 = Account::factory()->create(['business_id' => $this->business->id, 'type' => 'asset']);
    $this->account2 = Account::factory()->create(['business_id' => $this->business->id, 'type' => 'expense']);
});

it('can load the recurring journal entries index page', function () {
    $this->get(route('accounting.recurring-journal-entries.index'))
        ->assertOk();
});

it('can load the create recurring journal entry page', function () {
    $this->get(route('accounting.recurring-journal-entries.create'))
        ->assertOk();
});

it('can create a recurring journal entry', function () {
    $this->post(route('accounting.recurring-journal-entries.store'), [
        'name' => 'Monthly Rent',
        'frequency' => 'monthly',
        'start_date' => '2026-01-01',
        'day_of_month' => 1,
        'template_lines' => [
            ['account_id' => $this->account1->id, 'debit' => 1000, 'credit' => 0, 'description' => 'Rent'],
            ['account_id' => $this->account2->id, 'debit' => 0, 'credit' => 1000, 'description' => 'Rent credit'],
        ],
    ])->assertRedirect();

    $this->assertDatabaseHas('recurring_journal_entries', [
        'name' => 'Monthly Rent',
        'business_id' => $this->business->id,
        'frequency' => 'monthly',
    ]);
});

it('validates template lines are balanced', function () {
    $this->post(route('accounting.recurring-journal-entries.store'), [
        'name' => 'Unbalanced Entry',
        'frequency' => 'monthly',
        'start_date' => '2026-01-01',
        'day_of_month' => 1,
        'template_lines' => [
            ['account_id' => $this->account1->id, 'debit' => 100, 'credit' => 0],
            ['account_id' => $this->account2->id, 'debit' => 0, 'credit' => 50],
        ],
    ])->assertSessionHasErrors();
});

it('can toggle active state of a recurring entry', function () {
    $entry = RecurringJournalEntry::factory()->create([
        'business_id' => $this->business->id,
        'is_active' => true,
        'template_lines' => [],
        'created_by' => $this->user->id,
    ]);

    $this->post(route('accounting.recurring-journal-entries.toggle-active', $entry))
        ->assertRedirect();

    $entry->refresh();
    expect($entry->is_active)->toBeFalse();
});

it('can delete a recurring entry', function () {
    $entry = RecurringJournalEntry::factory()->create([
        'business_id' => $this->business->id,
        'template_lines' => [],
        'created_by' => $this->user->id,
    ]);

    $this->delete(route('accounting.recurring-journal-entries.destroy', $entry))
        ->assertRedirect(route('accounting.recurring-journal-entries.index'));

    $this->assertDatabaseMissing('recurring_journal_entries', ['id' => $entry->id]);
});
