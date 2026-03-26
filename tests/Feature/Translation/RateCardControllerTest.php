<?php

use App\Domain\Translation\Enums\BillingUnit;
use App\Domain\Translation\Enums\RateCardType;
use App\Models\Contact;
use App\Models\Language;
use App\Models\LanguagePair;
use App\Models\RateCard;
use App\Models\RateCardVolumeTier;
use App\Models\ServiceType;
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

    $src = Language::factory()->create(['business_id' => $this->business->id, 'code' => 'EN', 'name' => 'English']);
    $tgt = Language::factory()->create(['business_id' => $this->business->id, 'code' => 'DE', 'name' => 'German']);

    $this->languagePair = LanguagePair::factory()->create([
        'business_id' => $this->business->id,
        'source_language_id' => $src->id,
        'target_language_id' => $tgt->id,
    ]);

    $this->serviceType = ServiceType::factory()->create([
        'business_id' => $this->business->id,
        'name' => 'Translation',
        'code' => 'translation',
        'default_unit' => BillingUnit::Word,
    ]);
});

it('can load the rate cards index', function () {
    RateCard::factory()->create([
        'business_id' => $this->business->id,
        'type' => RateCardType::Default->value,
        'contact_id' => null,
        'language_pair_id' => $this->languagePair->id,
        'service_type_id' => $this->serviceType->id,
        'unit_rate' => '0.1200',
        'unit' => BillingUnit::Word->value,
    ]);

    $this->get(route('translation.rate-cards.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('translation/rate-cards/index')
            ->has('rateCards.data', 1)
            ->has('rateCardTypes')
        );
});

it('can load the create rate card page', function () {
    $this->get(route('translation.rate-cards.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('translation/rate-cards/create')
            ->has('languagePairs')
            ->has('serviceTypes')
            ->has('contacts')
            ->has('rateCardTypes')
            ->has('billingUnits')
        );
});

it('can create a default rate card', function () {
    $this->post(route('translation.rate-cards.store'), [
        'type' => RateCardType::Default->value,
        'contact_id' => null,
        'language_pair_id' => $this->languagePair->id,
        'service_type_id' => $this->serviceType->id,
        'unit_rate' => 0.12,
        'unit' => BillingUnit::Word->value,
    ])->assertRedirect(route('translation.rate-cards.index'));

    $this->assertDatabaseHas('rate_cards', [
        'business_id' => $this->business->id,
        'type' => 'default',
        'contact_id' => null,
        'language_pair_id' => $this->languagePair->id,
        'service_type_id' => $this->serviceType->id,
        'unit' => 'word',
        'is_active' => true,
    ]);
});

it('can create a client rate card with optional pricing fields', function () {
    $client = Contact::factory()->create([
        'business_id' => $this->business->id,
        'type' => 'customer',
        'name' => 'Acme Corp',
    ]);

    $this->post(route('translation.rate-cards.store'), [
        'type' => RateCardType::Client->value,
        'contact_id' => $client->id,
        'language_pair_id' => $this->languagePair->id,
        'service_type_id' => $this->serviceType->id,
        'unit_rate' => 0.15,
        'unit' => BillingUnit::Word->value,
        'minimum_fee' => 25.00,
        'rush_multiplier' => 1.50,
        'rush_fixed_surcharge' => 50.00,
    ])->assertRedirect(route('translation.rate-cards.index'));

    $this->assertDatabaseHas('rate_cards', [
        'business_id' => $this->business->id,
        'type' => 'client',
        'contact_id' => $client->id,
    ]);
});

it('can create a rate card with volume tiers', function () {
    $this->post(route('translation.rate-cards.store'), [
        'type' => RateCardType::Default->value,
        'language_pair_id' => $this->languagePair->id,
        'service_type_id' => $this->serviceType->id,
        'unit_rate' => 0.12,
        'unit' => BillingUnit::Word->value,
        'volume_tiers' => [
            ['minimum_words' => 5000, 'unit_rate' => 0.10],
            ['minimum_words' => 10000, 'unit_rate' => 0.09],
        ],
    ])->assertRedirect(route('translation.rate-cards.index'));

    $rateCard = RateCard::where('business_id', $this->business->id)->first();

    expect($rateCard->volumeTiers)->toHaveCount(2);
    expect($rateCard->volumeTiers->first()->minimum_words)->toBe(5000);
    expect($rateCard->volumeTiers->last()->minimum_words)->toBe(10000);
});

it('requires a contact for client rate cards', function () {
    $this->post(route('translation.rate-cards.store'), [
        'type' => RateCardType::Client->value,
        'language_pair_id' => $this->languagePair->id,
        'service_type_id' => $this->serviceType->id,
        'unit_rate' => 0.15,
        'unit' => BillingUnit::Word->value,
    ])->assertSessionHasErrors('contact_id');
});

it('requires a contact for translator rate cards', function () {
    $this->post(route('translation.rate-cards.store'), [
        'type' => RateCardType::Translator->value,
        'language_pair_id' => $this->languagePair->id,
        'service_type_id' => $this->serviceType->id,
        'unit_rate' => 0.08,
        'unit' => BillingUnit::Word->value,
    ])->assertSessionHasErrors('contact_id');
});

it('enforces uniqueness per business type contact language-pair service-type', function () {
    RateCard::factory()->create([
        'business_id' => $this->business->id,
        'type' => RateCardType::Default->value,
        'contact_id' => null,
        'language_pair_id' => $this->languagePair->id,
        'service_type_id' => $this->serviceType->id,
        'unit_rate' => '0.1200',
        'unit' => BillingUnit::Word->value,
    ]);

    $this->post(route('translation.rate-cards.store'), [
        'type' => RateCardType::Default->value,
        'language_pair_id' => $this->languagePair->id,
        'service_type_id' => $this->serviceType->id,
        'unit_rate' => 0.13,
        'unit' => BillingUnit::Word->value,
    ])->assertSessionHasErrors();
});

it('can update a rate card', function () {
    $rateCard = RateCard::factory()->create([
        'business_id' => $this->business->id,
        'type' => RateCardType::Default->value,
        'contact_id' => null,
        'language_pair_id' => $this->languagePair->id,
        'service_type_id' => $this->serviceType->id,
        'unit_rate' => '0.1200',
        'unit' => BillingUnit::Word->value,
    ]);

    $this->put(route('translation.rate-cards.update', $rateCard), [
        'type' => RateCardType::Default->value,
        'language_pair_id' => $this->languagePair->id,
        'service_type_id' => $this->serviceType->id,
        'unit_rate' => 0.14,
        'unit' => BillingUnit::Word->value,
        'notes' => 'Updated rate',
        'is_active' => true,
    ])->assertRedirect(route('translation.rate-cards.index'));

    expect($rateCard->fresh()->notes)->toBe('Updated rate');
    expect((float) $rateCard->fresh()->unit_rate)->toBe(0.14);
});

it('replaces volume tiers on update', function () {
    $rateCard = RateCard::factory()->create([
        'business_id' => $this->business->id,
        'type' => RateCardType::Default->value,
        'contact_id' => null,
        'language_pair_id' => $this->languagePair->id,
        'service_type_id' => $this->serviceType->id,
        'unit_rate' => '0.1200',
        'unit' => BillingUnit::Word->value,
    ]);
    RateCardVolumeTier::create(['rate_card_id' => $rateCard->id, 'minimum_words' => 5000, 'unit_rate' => '0.1000']);

    $this->put(route('translation.rate-cards.update', $rateCard), [
        'type' => RateCardType::Default->value,
        'language_pair_id' => $this->languagePair->id,
        'service_type_id' => $this->serviceType->id,
        'unit_rate' => 0.12,
        'unit' => BillingUnit::Word->value,
        'volume_tiers' => [
            ['minimum_words' => 10000, 'unit_rate' => 0.09],
        ],
        'is_active' => true,
    ])->assertRedirect(route('translation.rate-cards.index'));

    expect($rateCard->fresh()->volumeTiers)->toHaveCount(1);
    expect($rateCard->fresh()->volumeTiers->first()->minimum_words)->toBe(10000);
});

it('can delete a rate card', function () {
    $rateCard = RateCard::factory()->create([
        'business_id' => $this->business->id,
        'type' => RateCardType::Default->value,
        'contact_id' => null,
        'language_pair_id' => $this->languagePair->id,
        'service_type_id' => $this->serviceType->id,
        'unit_rate' => '0.1200',
        'unit' => BillingUnit::Word->value,
    ]);

    $this->delete(route('translation.rate-cards.destroy', $rateCard))
        ->assertRedirect(route('translation.rate-cards.index'));

    $this->assertDatabaseMissing('rate_cards', ['id' => $rateCard->id]);
});

it('filters rate cards by type', function () {
    $defaultCard = RateCard::factory()->create([
        'business_id' => $this->business->id,
        'type' => RateCardType::Default->value,
        'contact_id' => null,
        'language_pair_id' => $this->languagePair->id,
        'service_type_id' => $this->serviceType->id,
        'unit_rate' => '0.1200',
        'unit' => BillingUnit::Word->value,
    ]);

    $this->get(route('translation.rate-cards.index', ['type' => 'default']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('rateCards.data', 1));

    $this->get(route('translation.rate-cards.index', ['type' => 'client']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('rateCards.data', 0));
});
