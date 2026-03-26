<?php

use App\Models\Language;
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

it('can load the languages index', function () {
    Language::factory()->create(['business_id' => $this->business->id, 'code' => 'en', 'name' => 'English']);

    $this->get(route('translation.languages.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('translation/languages/index')
            ->has('languages.data', 1)
        );
});

it('can search languages by name', function () {
    Language::factory()->create(['business_id' => $this->business->id, 'code' => 'en', 'name' => 'English']);
    Language::factory()->create(['business_id' => $this->business->id, 'code' => 'fr', 'name' => 'French']);

    $this->get(route('translation.languages.index', ['search' => 'Eng']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('languages.data', 1));
});

it('can load the create language page', function () {
    $this->get(route('translation.languages.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('translation/languages/create'));
});

it('can create a language', function () {
    $this->post(route('translation.languages.store'), [
        'code' => 'en',
        'name' => 'English',
        'native_name' => 'English',
    ])->assertRedirect(route('translation.languages.index'));

    $this->assertDatabaseHas('languages', [
        'business_id' => $this->business->id,
        'code' => 'en',
        'name' => 'English',
        'native_name' => 'English',
        'is_active' => true,
    ]);
});

it('cannot create a language with a duplicate code in the same business', function () {
    Language::factory()->create(['business_id' => $this->business->id, 'code' => 'en', 'name' => 'English']);

    $this->post(route('translation.languages.store'), [
        'code' => 'en',
        'name' => 'English (US)',
    ])->assertSessionHasErrors('code');
});

it('can load the edit language page', function () {
    $language = Language::factory()->create(['business_id' => $this->business->id, 'code' => 'en', 'name' => 'English']);

    $this->get(route('translation.languages.edit', $language))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('translation/languages/create')
            ->where('language.id', $language->id)
        );
});

it('can update a language', function () {
    $language = Language::factory()->create(['business_id' => $this->business->id, 'code' => 'en', 'name' => 'English']);

    $this->put(route('translation.languages.update', $language), [
        'code' => 'en',
        'name' => 'English (UK)',
        'native_name' => 'English',
        'is_active' => true,
    ])->assertRedirect(route('translation.languages.index'));

    expect($language->fresh()->name)->toBe('English (UK)');
});

it('can delete a language', function () {
    $language = Language::factory()->create(['business_id' => $this->business->id, 'code' => 'en', 'name' => 'English']);

    $this->delete(route('translation.languages.destroy', $language))
        ->assertRedirect(route('translation.languages.index'));

    $this->assertDatabaseMissing('languages', ['id' => $language->id]);
});

it('cannot access languages of another business', function () {
    $otherUser = User::factory()->create();
    $otherBusiness = app(BusinessSetupService::class)->createBusiness($otherUser, [
        'name' => 'Other Business',
        'currency_code' => 'USD',
        'country' => 'US',
    ]);
    $language = Language::factory()->create(['business_id' => $otherBusiness->id, 'code' => 'en', 'name' => 'English']);

    $this->put(route('translation.languages.update', $language), [
        'code' => 'en',
        'name' => 'Hacked',
    ])->assertStatus(404);
});
