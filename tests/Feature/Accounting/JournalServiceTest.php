<?php

use App\Models\Account;
use App\Models\Business;
use App\Models\JournalEntry;
use App\Models\User;
use App\Services\Accounting\JournalService;
use Illuminate\Support\Facades\Auth;

beforeEach(function () {
    $this->business = Business::factory()->create();
    $this->user = User::factory()->create();
    $this->business->users()->attach($this->user, ['role' => 'owner']);
    Auth::login($this->user);

    $this->journalService = app(JournalService::class);

    // Create some accounts for testing
    $this->account1 = Account::factory()->create(['business_id' => $this->business->id]);
    $this->account2 = Account::factory()->create(['business_id' => $this->business->id]);
});

it('can create and post a journal entry', function () {
    $lines = [
        ['account_id' => $this->account1->id, 'debit' => 100, 'credit' => 0, 'description' => 'Debit line'],
        ['account_id' => $this->account2->id, 'debit' => 0, 'credit' => 100, 'description' => 'Credit line'],
    ];

    $entry = $this->journalService->createAndPost(
        business: $this->business,
        date: now(),
        lines: $lines,
        description: 'Test Journal Entry'
    );

    expect($entry)->toBeInstanceOf(JournalEntry::class)
        ->and($entry->is_posted)->toBeTrue()
        ->and($entry->lines)->toHaveCount(2)
        ->and($entry->totalDebit())->toBe(100.0)
        ->and($entry->totalCredit())->toBe(100.0);

    $this->assertDatabaseHas('journal_entries', [
        'id' => $entry->id,
        'business_id' => $this->business->id,
        'is_posted' => true,
    ]);
});

it('can create a draft journal entry', function () {
    $lines = [
        ['account_id' => $this->account1->id, 'debit' => 250, 'credit' => 0],
        ['account_id' => $this->account2->id, 'debit' => 0, 'credit' => 250],
    ];

    $entry = $this->journalService->createDraft(
        business: $this->business,
        date: now(),
        lines: $lines,
        description: 'Draft Entry'
    );

    expect($entry->is_posted)->toBeFalse()
        ->and($entry->posted_at)->toBeNull();
});

it('can post a draft journal entry', function () {
    $entry = JournalEntry::factory()->draft()->create(['business_id' => $this->business->id]);
    Account::factory()->create(['id' => 1, 'business_id' => $this->business->id]); // Ensure accounts exist if factory uses them

    // Manually add lines to make it balanced
    $entry->lines()->create(['account_id' => $this->account1->id, 'debit' => 100, 'credit' => 0]);
    $entry->lines()->create(['account_id' => $this->account2->id, 'debit' => 0, 'credit' => 100]);

    $postedEntry = $this->journalService->post($entry);

    expect($postedEntry->is_posted)->toBeTrue()
        ->and($postedEntry->posted_at)->not->toBeNull();
});

it('throws exception when posting unbalanced entry', function () {
    $entry = JournalEntry::factory()->draft()->create(['business_id' => $this->business->id]);
    $entry->lines()->create(['account_id' => $this->account1->id, 'debit' => 100, 'credit' => 0]);
    $entry->lines()->create(['account_id' => $this->account2->id, 'debit' => 0, 'credit' => 99.99]);

    $this->journalService->post($entry);
})->throws(DomainException::class, 'Cannot post an unbalanced journal entry.');

it('can reverse a posted journal entry', function () {
    $lines = [
        ['account_id' => $this->account1->id, 'debit' => 500, 'credit' => 0, 'description' => 'Original Debit'],
        ['account_id' => $this->account2->id, 'debit' => 0, 'credit' => 500, 'description' => 'Original Credit'],
    ];

    $original = $this->journalService->createAndPost(
        business: $this->business,
        date: now(),
        lines: $lines,
        description: 'Original Entry'
    );

    $reversal = $this->journalService->reverse($original, 'Correction');

    expect($reversal->is_posted)->toBeTrue()
        ->and($reversal->reversal_of_id)->toBe($original->id)
        ->and($reversal->totalDebit())->toBe(500.0)
        ->and($reversal->totalCredit())->toBe(500.0);

    // Check that lines are swapped
    $debitLine = $reversal->lines()->where('debit', '>', 0)->first();
    $creditLine = $reversal->lines()->where('credit', '>', 0)->first();

    expect($debitLine->account_id)->toBe($this->account2->id)
        ->and($creditLine->account_id)->toBe($this->account1->id);
});

it('throws exception if reversing already reversed entry', function () {
    $lines = [
        ['account_id' => $this->account1->id, 'debit' => 100, 'credit' => 0],
        ['account_id' => $this->account2->id, 'debit' => 0, 'credit' => 100],
    ];

    $original = $this->journalService->createAndPost($this->business, now(), $lines, 'Test');
    $this->journalService->reverse($original, 'First reversal');

    $this->journalService->reverse($original, 'Second reversal');
})->throws(DomainException::class, 'This entry has already been reversed.');
