<?php

use App\Models\Language;
use App\Models\LanguagePair;
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

    $this->english = Language::factory()->create(['business_id' => $this->business->id, 'code' => 'en', 'name' => 'English']);
    $this->french = Language::factory()->create(['business_id' => $this->business->id, 'code' => 'fr', 'name' => 'French']);
    $this->german = Language::factory()->create(['business_id' => $this->business->id, 'code' => 'de', 'name' => 'German']);
});

it('can load the language pairs index', function () {
    LanguagePair::factory()->create([
        'business_id' => $this->business->id,
        'source_language_id' => $this->english->id,
        'target_language_id' => $this->french->id,
    ]);

    $this->get(route('translation.language-pairs.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('translation/language-pairs/index')
            ->has('pairs.data', 1)
        );
});

it('can load the create language pair page', function () {
    $this->get(route('translation.language-pairs.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('translation/language-pairs/create')
            ->has('languages')
        );
});

it('can create a language pair', function () {
    $this->post(route('translation.language-pairs.store'), [
        'source_language_id' => $this->english->id,
        'target_language_id' => $this->french->id,
    ])->assertRedirect(route('translation.language-pairs.index'));

    $this->assertDatabaseHas('language_pairs', [
        'business_id' => $this->business->id,
        'source_language_id' => $this->english->id,
        'target_language_id' => $this->french->id,
        'is_active' => true,
    ]);
});

it('cannot create a language pair with the same source and target language', function () {
    $this->post(route('translation.language-pairs.store'), [
        'source_language_id' => $this->english->id,
        'target_language_id' => $this->english->id,
    ])->assertSessionHasErrors('target_language_id');
});

it('cannot create a duplicate language pair', function () {
    LanguagePair::factory()->create([
        'business_id' => $this->business->id,
        'source_language_id' => $this->english->id,
        'target_language_id' => $this->french->id,
    ]);

    $this->post(route('translation.language-pairs.store'), [
        'source_language_id' => $this->english->id,
        'target_language_id' => $this->french->id,
    ])->assertSessionHasErrors('target_language_id');
});

it('can update a language pair', function () {
    $pair = LanguagePair::factory()->create([
        'business_id' => $this->business->id,
        'source_language_id' => $this->english->id,
        'target_language_id' => $this->french->id,
    ]);

    $this->put(route('translation.language-pairs.update', $pair), [
        'source_language_id' => $this->english->id,
        'target_language_id' => $this->german->id,
        'is_active' => true,
    ])->assertRedirect(route('translation.language-pairs.index'));

    expect($pair->fresh()->target_language_id)->toBe($this->german->id);
});

it('can delete a language pair', function () {
    $pair = LanguagePair::factory()->create([
        'business_id' => $this->business->id,
        'source_language_id' => $this->english->id,
        'target_language_id' => $this->french->id,
    ]);

    $this->delete(route('translation.language-pairs.destroy', $pair))
        ->assertRedirect(route('translation.language-pairs.index'));

    $this->assertDatabaseMissing('language_pairs', ['id' => $pair->id]);
});
