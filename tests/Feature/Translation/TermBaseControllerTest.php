<?php

use App\Models\TermBase;
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
});

it('can load the term bases index', function () {
    TermBase::factory()->create(['business_id' => $this->business->id, 'name' => 'Legal Glossary']);

    $this->get(route('translation.term-bases.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('translation/term-bases/index')
            ->has('termBases.data', 1)
        );
});

it('can search term bases by name', function () {
    TermBase::factory()->create(['business_id' => $this->business->id, 'name' => 'Legal Glossary']);
    TermBase::factory()->create(['business_id' => $this->business->id, 'name' => 'Marketing Terms']);

    $this->get(route('translation.term-bases.index', ['search' => 'Legal']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('termBases.data', 1));
});

it('can load the create term base page', function () {
    $this->get(route('translation.term-bases.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('translation/term-bases/create')
            ->has('customers')
            ->has('subjectFields')
        );
});

it('can create a term base', function () {
    $this->post(route('translation.term-bases.store'), [
        'name' => 'Acme Legal Glossary',
        'subject_field' => 'Legal',
        'description' => 'Legal terminology for Acme Corp',
    ])->assertRedirect(route('translation.term-bases.index'));

    $this->assertDatabaseHas('term_bases', [
        'business_id' => $this->business->id,
        'name' => 'Acme Legal Glossary',
        'subject_field' => 'Legal',
    ]);
});

it('validates required fields when creating a term base', function () {
    $this->post(route('translation.term-bases.store'), [])
        ->assertSessionHasErrors(['name']);
});

it('can load the edit term base page', function () {
    $tb = TermBase::factory()->create(['business_id' => $this->business->id]);

    $this->get(route('translation.term-bases.edit', $tb))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('translation/term-bases/edit')
            ->where('termBase.id', $tb->id)
        );
});

it('can update a term base', function () {
    $tb = TermBase::factory()->create(['business_id' => $this->business->id, 'name' => 'Old Name']);

    $this->put(route('translation.term-bases.update', $tb), [
        'name' => 'Updated Glossary',
    ])->assertRedirect(route('translation.term-bases.index'));

    expect($tb->fresh()->name)->toBe('Updated Glossary');
});

it('can delete a term base', function () {
    $tb = TermBase::factory()->create(['business_id' => $this->business->id]);

    $this->delete(route('translation.term-bases.destroy', $tb))
        ->assertRedirect(route('translation.term-bases.index'));

    $this->assertDatabaseMissing('term_bases', ['id' => $tb->id]);
});

it('cannot access term bases of another business', function () {
    $otherUser = User::factory()->create();
    $otherBusiness = app(BusinessSetupService::class)->createBusiness($otherUser, [
        'name' => 'Other Business',
        'currency_code' => 'USD',
        'country' => 'US',
    ]);
    $tb = TermBase::factory()->create(['business_id' => $otherBusiness->id]);

    $this->put(route('translation.term-bases.update', $tb), ['name' => 'Hacked'])
        ->assertStatus(404);
});
