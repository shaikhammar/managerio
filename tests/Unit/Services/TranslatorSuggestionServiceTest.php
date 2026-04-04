<?php

use App\Domain\Translation\Enums\TranslatorAvailability;
use App\Models\Business;
use App\Models\Contact;
use App\Models\LanguagePair;
use App\Models\ServiceType;
use App\Models\TranslatorProfile;
use App\Services\Translation\TranslatorSuggestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->business = Business::factory()->create();
    $this->lp = LanguagePair::factory()->create(['business_id' => $this->business->id]);
    $this->st = ServiceType::factory()->create(['business_id' => $this->business->id]);
    $this->service = app(TranslatorSuggestionService::class);
});

it('returns translators matching language pair and service type sorted by score', function () {
    $best = TranslatorProfile::factory()->create([
        'business_id' => $this->business->id,
        'contact_id' => Contact::factory()->supplier()->create(['business_id' => $this->business->id])->id,
        'availability' => TranslatorAvailability::Available,
        'quality_rating' => 5,
    ]);
    $best->languagePairs()->attach($this->lp->id);
    $best->serviceTypes()->attach($this->st->id);

    $ok = TranslatorProfile::factory()->create([
        'business_id' => $this->business->id,
        'contact_id' => Contact::factory()->supplier()->create(['business_id' => $this->business->id])->id,
        'availability' => TranslatorAvailability::Busy,
        'quality_rating' => 3,
    ]);
    $ok->languagePairs()->attach($this->lp->id);
    $ok->serviceTypes()->attach($this->st->id);

    $results = $this->service->suggest($this->business, $this->lp->id, $this->st->id);

    expect($results)->toHaveCount(2);
    expect($results->first()['contact_id'])->toBe($best->contact_id);
});

it('excludes translators not matching the language pair', function () {
    $otherLp = LanguagePair::factory()->create(['business_id' => $this->business->id]);
    $profile = TranslatorProfile::factory()->create([
        'business_id' => $this->business->id,
        'contact_id' => Contact::factory()->supplier()->create(['business_id' => $this->business->id])->id,
    ]);
    $profile->languagePairs()->attach($otherLp->id);
    $profile->serviceTypes()->attach($this->st->id);
    $results = $this->service->suggest($this->business, $this->lp->id, $this->st->id);
    expect($results)->toBeEmpty();
});

it('excludes translators not matching the service type', function () {
    $otherSt = ServiceType::factory()->create(['business_id' => $this->business->id]);
    $profile = TranslatorProfile::factory()->create([
        'business_id' => $this->business->id,
        'contact_id' => Contact::factory()->supplier()->create(['business_id' => $this->business->id])->id,
    ]);
    $profile->languagePairs()->attach($this->lp->id);
    $profile->serviceTypes()->attach($otherSt->id);
    $results = $this->service->suggest($this->business, $this->lp->id, $this->st->id);
    expect($results)->toBeEmpty();
});

it('on_leave translators score below available and busy', function () {
    $onLeave = TranslatorProfile::factory()->create([
        'business_id' => $this->business->id,
        'contact_id' => Contact::factory()->supplier()->create(['business_id' => $this->business->id])->id,
        'availability' => TranslatorAvailability::OnLeave,
        'quality_rating' => 5,
    ]);
    $onLeave->languagePairs()->attach($this->lp->id);
    $onLeave->serviceTypes()->attach($this->st->id);

    $available = TranslatorProfile::factory()->create([
        'business_id' => $this->business->id,
        'contact_id' => Contact::factory()->supplier()->create(['business_id' => $this->business->id])->id,
        'availability' => TranslatorAvailability::Available,
        'quality_rating' => 0,
    ]);
    $available->languagePairs()->attach($this->lp->id);
    $available->serviceTypes()->attach($this->st->id);

    $results = $this->service->suggest($this->business, $this->lp->id, $this->st->id);
    expect($results->first()['contact_id'])->toBe($available->contact_id);
});

it('returns at most 5 results', function () {
    for ($i = 0; $i < 7; $i++) {
        $p = TranslatorProfile::factory()->create([
            'business_id' => $this->business->id,
            'contact_id' => Contact::factory()->supplier()->create(['business_id' => $this->business->id])->id,
        ]);
        $p->languagePairs()->attach($this->lp->id);
        $p->serviceTypes()->attach($this->st->id);
    }
    $results = $this->service->suggest($this->business, $this->lp->id, $this->st->id);
    expect($results)->toHaveCount(5);
});

it('returns empty collection when no translators match', function () {
    $results = $this->service->suggest($this->business, $this->lp->id, $this->st->id);
    expect($results)->toBeEmpty();
});
