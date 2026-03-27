<?php

use App\Domain\Translation\Enums\TranslatorAvailability;
use App\Models\Contact;
use App\Models\Language;
use App\Models\LanguagePair;
use App\Models\ServiceType;
use App\Models\TranslatorProfile;
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

    $this->supplier = Contact::factory()->supplier()->create(['business_id' => $this->business->id]);

    $this->english = Language::factory()->create(['business_id' => $this->business->id, 'code' => 'en', 'name' => 'English']);
    $this->french = Language::factory()->create(['business_id' => $this->business->id, 'code' => 'fr', 'name' => 'French']);
    $this->langPair = LanguagePair::factory()->create([
        'business_id' => $this->business->id,
        'source_language_id' => $this->english->id,
        'target_language_id' => $this->french->id,
    ]);

    $this->serviceType = ServiceType::factory()->create([
        'business_id' => $this->business->id,
        'name' => 'Translation',
        'code' => 'TRN',
    ]);
});

it('can load the translators index', function () {
    TranslatorProfile::factory()->create([
        'business_id' => $this->business->id,
        'contact_id' => $this->supplier->id,
    ]);

    $this->get(route('translation.translators.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('translation/translators/index')
            ->has('translators.data', 1)
            ->has('availabilities')
        );
});

it('can filter translators by availability', function () {
    $available = Contact::factory()->supplier()->create(['business_id' => $this->business->id]);
    $busy = Contact::factory()->supplier()->create(['business_id' => $this->business->id]);

    TranslatorProfile::factory()->create([
        'business_id' => $this->business->id,
        'contact_id' => $available->id,
        'availability' => TranslatorAvailability::Available->value,
    ]);

    TranslatorProfile::factory()->create([
        'business_id' => $this->business->id,
        'contact_id' => $busy->id,
        'availability' => TranslatorAvailability::Busy->value,
    ]);

    $this->get(route('translation.translators.index', ['availability' => 'available']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('translators.data', 1));
});

it('can load the create translator profile page', function () {
    $this->get(route('translation.translators.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('translation/translators/create')
            ->has('contacts')
            ->has('languagePairs')
            ->has('serviceTypes')
            ->has('availabilities')
            ->has('specialisations')
            ->has('catTools')
            ->has('certifications')
        );
});

it('can create a translator profile', function () {
    $this->post(route('translation.translators.store'), [
        'contact_id' => $this->supplier->id,
        'availability' => 'available',
        'quality_rating' => 4,
        'quality_notes' => 'Very reliable.',
        'specialisations' => ['legal', 'technical'],
        'cat_tools' => ['trados', 'memoq'],
        'certifications' => ['iso_17100'],
        'language_pair_ids' => [$this->langPair->id],
        'service_type_ids' => [$this->serviceType->id],
    ])->assertRedirect();

    $this->assertDatabaseHas('translator_profiles', [
        'business_id' => $this->business->id,
        'contact_id' => $this->supplier->id,
        'availability' => 'available',
        'quality_rating' => 4,
    ]);

    $profile = TranslatorProfile::where('contact_id', $this->supplier->id)->first();
    expect($profile)->not->toBeNull();
    expect($profile->languagePairs()->count())->toBe(1);
    expect($profile->serviceTypes()->count())->toBe(1);
    expect($profile->specialisations)->toContain('legal');
    expect($profile->specialisations)->toContain('technical');
});

it('validates required fields on create', function () {
    $this->post(route('translation.translators.store'), [])
        ->assertSessionHasErrors(['contact_id', 'availability']);
});

it('cannot create a duplicate profile for the same contact', function () {
    TranslatorProfile::factory()->create([
        'business_id' => $this->business->id,
        'contact_id' => $this->supplier->id,
    ]);

    $this->post(route('translation.translators.store'), [
        'contact_id' => $this->supplier->id,
        'availability' => 'available',
    ])->assertSessionHasErrors('contact_id');
});

it('validates quality rating must be between 1 and 5', function () {
    $this->post(route('translation.translators.store'), [
        'contact_id' => $this->supplier->id,
        'availability' => 'available',
        'quality_rating' => 6,
    ])->assertSessionHasErrors('quality_rating');
});

it('can show a translator profile', function () {
    $profile = TranslatorProfile::factory()->create([
        'business_id' => $this->business->id,
        'contact_id' => $this->supplier->id,
    ]);

    $this->get(route('translation.translators.show', $profile))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('translation/translators/show')
            ->where('translator.id', $profile->id)
        );
});

it('can load the edit translator profile page', function () {
    $profile = TranslatorProfile::factory()->create([
        'business_id' => $this->business->id,
        'contact_id' => $this->supplier->id,
    ]);

    $this->get(route('translation.translators.edit', $profile))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('translation/translators/create')
            ->where('translator.id', $profile->id)
        );
});

it('can update a translator profile', function () {
    $profile = TranslatorProfile::factory()->create([
        'business_id' => $this->business->id,
        'contact_id' => $this->supplier->id,
        'availability' => TranslatorAvailability::Available->value,
    ]);

    $this->put(route('translation.translators.update', $profile), [
        'contact_id' => $this->supplier->id,
        'availability' => 'busy',
        'quality_rating' => 5,
        'quality_notes' => 'Excellent quality.',
        'specialisations' => ['medical'],
        'cat_tools' => [],
        'certifications' => ['naati'],
        'language_pair_ids' => [$this->langPair->id],
        'service_type_ids' => [],
    ])->assertRedirect(route('translation.translators.show', $profile));

    expect($profile->fresh()->availability)->toBe(TranslatorAvailability::Busy);
    expect($profile->fresh()->quality_rating)->toBe(5);
    expect($profile->fresh()->specialisations)->toContain('medical');
});

it('syncs language pairs on update', function () {
    $profile = TranslatorProfile::factory()->create([
        'business_id' => $this->business->id,
        'contact_id' => $this->supplier->id,
    ]);
    $profile->languagePairs()->attach($this->langPair->id);

    $german = Language::factory()->create(['business_id' => $this->business->id, 'code' => 'de', 'name' => 'German']);
    $deLangPair = LanguagePair::factory()->create([
        'business_id' => $this->business->id,
        'source_language_id' => $this->english->id,
        'target_language_id' => $german->id,
    ]);

    $this->put(route('translation.translators.update', $profile), [
        'contact_id' => $this->supplier->id,
        'availability' => 'available',
        'language_pair_ids' => [$deLangPair->id],
        'service_type_ids' => [],
    ])->assertRedirect();

    expect($profile->fresh()->languagePairs()->pluck('language_pair_id')->toArray())->toBe([$deLangPair->id]);
});

it('can delete a translator profile', function () {
    $profile = TranslatorProfile::factory()->create([
        'business_id' => $this->business->id,
        'contact_id' => $this->supplier->id,
    ]);

    $this->delete(route('translation.translators.destroy', $profile))
        ->assertRedirect(route('translation.translators.index'));

    $this->assertDatabaseMissing('translator_profiles', ['id' => $profile->id]);
});
