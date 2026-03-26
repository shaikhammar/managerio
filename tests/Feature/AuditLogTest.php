<?php

use App\Domain\Shared\Enums\BusinessRole;
use App\Models\AuditLog;
use App\Models\Business;
use App\Models\Contact;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->business = setupBusiness($this->user);
    $this->actingAs($this->user);
});

test('creating a contact records an audit log entry', function () {
    $contact = Contact::factory()->create([
        'business_id' => $this->business->id,
        'name' => 'Audit Test Customer',
    ]);

    $log = AuditLog::where('auditable_type', Contact::class)
        ->where('auditable_id', $contact->id)
        ->where('event', 'created')
        ->first();

    expect($log)->not->toBeNull();
    expect($log->business_id)->toBe($this->business->id);
    expect($log->auditable_label)->toBe('Audit Test Customer');
});

test('updating a contact records an updated audit log entry', function () {
    $contact = Contact::factory()->create([
        'business_id' => $this->business->id,
        'name' => 'Original Name',
    ]);

    $contact->update(['name' => 'Updated Name']);

    $log = AuditLog::where('auditable_type', Contact::class)
        ->where('auditable_id', $contact->id)
        ->where('event', 'updated')
        ->first();

    expect($log)->not->toBeNull();
    expect($log->old_values)->toHaveKey('name');
    expect($log->old_values['name'])->toBe('Original Name');
    expect($log->new_values['name'])->toBe('Updated Name');
});

test('audit log index page is accessible', function () {
    $this->get(route('audit-log.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('audit-log/index')
            ->has('logs')
        );
});

test('audit log is scoped to current business', function () {
    $otherUser = User::factory()->create();
    $otherBusiness = Business::factory()->create();
    $otherBusiness->users()->attach($otherUser, ['role' => BusinessRole::OWNER->value]);

    // Create a contact for the other business directly (no session change)
    $otherContact = Contact::factory()->create([
        'business_id' => $otherBusiness->id,
        'name' => 'Other Business Contact',
    ]);

    $this->get(route('audit-log.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('logs')
            ->where('logs.data', fn ($data) => collect($data)->every(
                fn ($entry) => $entry['auditable_label'] !== 'Other Business Contact'
            ))
        );
});

test('audit log can be filtered by event type', function () {
    $contact = Contact::factory()->create(['business_id' => $this->business->id]);
    $contact->update(['name' => 'Changed Name']);

    $this->get(route('audit-log.index', ['event' => 'created']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('filters.event', 'created')
        );
});
