<?php

use App\Models\Language;
use App\Models\TranslationMemory;
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

    $this->sourceLang = Language::factory()->create(['business_id' => $this->business->id, 'code' => 'en', 'name' => 'English']);
    $this->targetLang = Language::factory()->create(['business_id' => $this->business->id, 'code' => 'de', 'name' => 'German']);
});

it('can load the translation memories index', function () {
    TranslationMemory::factory()->create([
        'business_id' => $this->business->id,
        'source_language_id' => $this->sourceLang->id,
        'target_language_id' => $this->targetLang->id,
        'name' => 'My TM',
    ]);

    $this->get(route('translation.translation-memories.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('translation/translation-memories/index')
            ->has('translationMemories.data', 1)
        );
});

it('can search translation memories by name', function () {
    TranslationMemory::factory()->create([
        'business_id' => $this->business->id,
        'source_language_id' => $this->sourceLang->id,
        'target_language_id' => $this->targetLang->id,
        'name' => 'Legal EN-DE',
    ]);
    TranslationMemory::factory()->create([
        'business_id' => $this->business->id,
        'source_language_id' => $this->sourceLang->id,
        'target_language_id' => $this->targetLang->id,
        'name' => 'Marketing TM',
    ]);

    $this->get(route('translation.translation-memories.index', ['search' => 'Legal']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('translationMemories.data', 1));
});

it('can load the create translation memory page', function () {
    $this->get(route('translation.translation-memories.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('translation/translation-memories/create')
            ->has('languages')
            ->has('customers')
        );
});

it('can create a translation memory', function () {
    $this->post(route('translation.translation-memories.store'), [
        'name' => 'Acme EN-DE TM',
        'source_language_id' => $this->sourceLang->id,
        'target_language_id' => $this->targetLang->id,
        'software' => 'memoQ',
        'notes' => 'Used for Acme legal docs',
    ])->assertRedirect(route('translation.translation-memories.index'));

    $this->assertDatabaseHas('translation_memories', [
        'business_id' => $this->business->id,
        'name' => 'Acme EN-DE TM',
        'software' => 'memoQ',
    ]);
});

it('validates required fields when creating a translation memory', function () {
    $this->post(route('translation.translation-memories.store'), [])
        ->assertSessionHasErrors(['name', 'source_language_id', 'target_language_id']);
});

it('can load the edit translation memory page', function () {
    $tm = TranslationMemory::factory()->create([
        'business_id' => $this->business->id,
        'source_language_id' => $this->sourceLang->id,
        'target_language_id' => $this->targetLang->id,
    ]);

    $this->get(route('translation.translation-memories.edit', $tm))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('translation/translation-memories/edit')
            ->where('translationMemory.id', $tm->id)
        );
});

it('can update a translation memory', function () {
    $tm = TranslationMemory::factory()->create([
        'business_id' => $this->business->id,
        'source_language_id' => $this->sourceLang->id,
        'target_language_id' => $this->targetLang->id,
        'name' => 'Old Name',
    ]);

    $this->put(route('translation.translation-memories.update', $tm), [
        'name' => 'Updated Name',
        'source_language_id' => $this->sourceLang->id,
        'target_language_id' => $this->targetLang->id,
    ])->assertRedirect(route('translation.translation-memories.index'));

    expect($tm->fresh()->name)->toBe('Updated Name');
});

it('can delete a translation memory', function () {
    $tm = TranslationMemory::factory()->create([
        'business_id' => $this->business->id,
        'source_language_id' => $this->sourceLang->id,
        'target_language_id' => $this->targetLang->id,
    ]);

    $this->delete(route('translation.translation-memories.destroy', $tm))
        ->assertRedirect(route('translation.translation-memories.index'));

    $this->assertDatabaseMissing('translation_memories', ['id' => $tm->id]);
});

it('cannot access translation memories of another business', function () {
    $otherUser = User::factory()->create();
    $otherBusiness = app(BusinessSetupService::class)->createBusiness($otherUser, [
        'name' => 'Other Business',
        'currency_code' => 'USD',
        'country' => 'US',
    ]);
    $otherLang = Language::factory()->create(['business_id' => $otherBusiness->id, 'code' => 'en', 'name' => 'English']);
    $tm = TranslationMemory::factory()->create([
        'business_id' => $otherBusiness->id,
        'source_language_id' => $otherLang->id,
        'target_language_id' => $otherLang->id,
    ]);

    $this->put(route('translation.translation-memories.update', $tm), [
        'name' => 'Hacked',
        'source_language_id' => $this->sourceLang->id,
        'target_language_id' => $this->targetLang->id,
    ])->assertStatus(404);
});
