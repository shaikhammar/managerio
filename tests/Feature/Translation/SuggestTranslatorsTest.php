<?php

use App\Domain\Translation\Enums\TranslatorAvailability;
use App\Models\Contact;
use App\Models\Language;
use App\Models\LanguagePair;
use App\Models\Project;
use App\Models\ServiceType;
use App\Models\TranslatorProfile;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->business = setupBusiness($this->user);

    $srcLang = Language::factory()->create(['business_id' => $this->business->id]);
    $tgtLang = Language::factory()->create(['business_id' => $this->business->id]);
    $this->lp = LanguagePair::factory()->create([
        'business_id' => $this->business->id,
        'source_language_id' => $srcLang->id,
        'target_language_id' => $tgtLang->id,
    ]);
    $this->st = ServiceType::factory()->create(['business_id' => $this->business->id]);

    $this->project = Project::factory()->create([
        'business_id' => $this->business->id,
        'source_language_id' => $srcLang->id,
        'service_type_id' => $this->st->id,
    ]);
});

it('returns ranked translator suggestions for a given language pair and service type', function () {
    $translator = Contact::factory()->supplier()->create([
        'business_id' => $this->business->id,
        'name' => 'Jane Smith',
    ]);

    $profile = TranslatorProfile::factory()->create([
        'business_id' => $this->business->id,
        'contact_id' => $translator->id,
        'availability' => TranslatorAvailability::Available,
        'quality_rating' => 4,
    ]);
    $profile->languagePairs()->attach($this->lp->id);
    $profile->serviceTypes()->attach($this->st->id);

    $this->actingAs($this->user)
        ->getJson("/translation/projects/{$this->project->id}/suggest-translators?language_pair_id={$this->lp->id}&service_type_id={$this->st->id}")
        ->assertOk()
        ->assertJsonCount(1)
        ->assertJsonFragment(['contact_id' => $translator->id, 'name' => 'Jane Smith']);
});

it('returns 422 if language_pair_id is missing', function () {
    $this->actingAs($this->user)
        ->getJson("/translation/projects/{$this->project->id}/suggest-translators?service_type_id={$this->st->id}")
        ->assertUnprocessable();
});

it('returns empty array when no translators match', function () {
    $this->actingAs($this->user)
        ->getJson("/translation/projects/{$this->project->id}/suggest-translators?language_pair_id={$this->lp->id}&service_type_id={$this->st->id}")
        ->assertOk()
        ->assertJsonCount(0);
});

it('requires authentication', function () {
    $this->getJson("/translation/projects/{$this->project->id}/suggest-translators?language_pair_id={$this->lp->id}&service_type_id={$this->st->id}")
        ->assertUnauthorized();
});
