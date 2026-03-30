<?php

use App\Domain\Translation\Enums\ProjectStatus;
use App\Models\Contact;
use App\Models\Language;
use App\Models\Project;
use App\Models\ServiceType;
use App\Models\StyleGuide;
use App\Models\TermBase;
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
    $this->serviceType = ServiceType::factory()->create(['business_id' => $this->business->id]);
    $this->customer = Contact::factory()->create(['business_id' => $this->business->id, 'type' => 'customer']);

    $this->project = Project::factory()->create([
        'business_id' => $this->business->id,
        'contact_id' => $this->customer->id,
        'source_language_id' => $this->sourceLang->id,
        'service_type_id' => $this->serviceType->id,
        'status' => ProjectStatus::NEW,
    ]);
});

// ── Translation Memory attach/detach ──────────────────────────────

it('can attach a translation memory to a project', function () {
    $tm = TranslationMemory::factory()->create([
        'business_id' => $this->business->id,
        'source_language_id' => $this->sourceLang->id,
        'target_language_id' => $this->targetLang->id,
    ]);

    $this->post(route('translation.projects.translation-memories.attach', [$this->project, $tm]))
        ->assertRedirect();

    $this->assertDatabaseHas('project_translation_memories', [
        'project_id' => $this->project->id,
        'translation_memory_id' => $tm->id,
    ]);
});

it('can detach a translation memory from a project', function () {
    $tm = TranslationMemory::factory()->create([
        'business_id' => $this->business->id,
        'source_language_id' => $this->sourceLang->id,
        'target_language_id' => $this->targetLang->id,
    ]);
    $this->project->translationMemories()->attach($tm->id);

    $this->delete(route('translation.projects.translation-memories.detach', [$this->project, $tm]))
        ->assertRedirect();

    $this->assertDatabaseMissing('project_translation_memories', [
        'project_id' => $this->project->id,
        'translation_memory_id' => $tm->id,
    ]);
});

it('attaching a translation memory twice does not create duplicates', function () {
    $tm = TranslationMemory::factory()->create([
        'business_id' => $this->business->id,
        'source_language_id' => $this->sourceLang->id,
        'target_language_id' => $this->targetLang->id,
    ]);

    $this->post(route('translation.projects.translation-memories.attach', [$this->project, $tm]));
    $this->post(route('translation.projects.translation-memories.attach', [$this->project, $tm]));

    expect($this->project->translationMemories()->count())->toBe(1);
});

// ── Term Base attach/detach ──────────────────────────────────────

it('can attach a term base to a project', function () {
    $tb = TermBase::factory()->create(['business_id' => $this->business->id]);

    $this->post(route('translation.projects.term-bases.attach', [$this->project, $tb]))
        ->assertRedirect();

    $this->assertDatabaseHas('project_term_bases', [
        'project_id' => $this->project->id,
        'term_base_id' => $tb->id,
    ]);
});

it('can detach a term base from a project', function () {
    $tb = TermBase::factory()->create(['business_id' => $this->business->id]);
    $this->project->termBases()->attach($tb->id);

    $this->delete(route('translation.projects.term-bases.detach', [$this->project, $tb]))
        ->assertRedirect();

    $this->assertDatabaseMissing('project_term_bases', [
        'project_id' => $this->project->id,
        'term_base_id' => $tb->id,
    ]);
});

// ── Style Guide attach/detach ────────────────────────────────────

it('can attach a style guide to a project', function () {
    $sg = StyleGuide::factory()->create(['business_id' => $this->business->id]);

    $this->post(route('translation.projects.style-guides.attach', [$this->project, $sg]))
        ->assertRedirect();

    $this->assertDatabaseHas('project_style_guides', [
        'project_id' => $this->project->id,
        'style_guide_id' => $sg->id,
    ]);
});

it('can detach a style guide from a project', function () {
    $sg = StyleGuide::factory()->create(['business_id' => $this->business->id]);
    $this->project->styleGuides()->attach($sg->id);

    $this->delete(route('translation.projects.style-guides.detach', [$this->project, $sg]))
        ->assertRedirect();

    $this->assertDatabaseMissing('project_style_guides', [
        'project_id' => $this->project->id,
        'style_guide_id' => $sg->id,
    ]);
});

// ── Business isolation ───────────────────────────────────────────

it('cannot attach a resource from another business to a project', function () {
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

    // The TM belongs to another business so route model binding returns 404
    $this->post(route('translation.projects.translation-memories.attach', [$this->project, $tm]))
        ->assertStatus(404);
});

// ── ProjectShow exposes resources ────────────────────────────────

it('project show page exposes attached and available resources', function () {
    $tm = TranslationMemory::factory()->create([
        'business_id' => $this->business->id,
        'source_language_id' => $this->sourceLang->id,
        'target_language_id' => $this->targetLang->id,
        'name' => 'Attached TM',
    ]);
    $availableTm = TranslationMemory::factory()->create([
        'business_id' => $this->business->id,
        'source_language_id' => $this->sourceLang->id,
        'target_language_id' => $this->targetLang->id,
        'name' => 'Available TM',
    ]);
    $this->project->translationMemories()->attach($tm->id);

    $this->get(route('translation.projects.show', $this->project))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('project.translation_memories', 1)
            ->has('availableTranslationMemories', 1)
            ->where('availableTranslationMemories.0.name', 'Available TM')
        );
});
