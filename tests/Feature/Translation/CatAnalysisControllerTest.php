<?php

use App\Domain\Translation\Enums\BillingUnit;
use App\Domain\Translation\Enums\CatMatchBand;
use App\Domain\Translation\Enums\CatTool;
use App\Domain\Translation\Enums\ProjectAssignmentRole;
use App\Models\CatAnalysis;
use App\Models\CatAnalysisBand;
use App\Models\Contact;
use App\Models\Language;
use App\Models\LanguagePair;
use App\Models\Project;
use App\Models\ProjectAssignment;
use App\Models\ProjectTarget;
use App\Models\ServiceType;
use App\Models\User;
use App\Services\Business\BusinessSetupService;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->business = app(BusinessSetupService::class)->createBusiness($this->user, [
        'name' => 'Test Agency',
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

    $this->client = Contact::factory()->create([
        'business_id' => $this->business->id,
        'name' => 'Acme Corp',
        'type' => 'customer',
    ]);

    $this->project = Project::factory()->create([
        'business_id' => $this->business->id,
        'contact_id' => $this->client->id,
        'source_language_id' => $src->id,
        'service_type_id' => $this->serviceType->id,
        'name' => 'Annual Report',
    ]);

    $this->target = ProjectTarget::factory()->create([
        'project_id' => $this->project->id,
        'language_pair_id' => $this->languagePair->id,
        'word_count' => 10000,
        'unit_price' => '0.1000',
    ]);
});

// ── Create / Store ───────────────────────────────────────────────

it('can load the create CAT analysis page', function () {
    $this->get(route('translation.projects.cat-analyses.create', $this->project))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('translation/projects/cat-analyses/create')
            ->has('project')
            ->has('targets')
            ->has('bands')
            ->has('tools')
        );
});

it('can create a CAT analysis manually', function () {
    $bands = collect(CatMatchBand::cases())->map(fn ($b) => [
        'band' => $b->value,
        'words' => match ($b) {
            CatMatchBand::ExactMatch => 2000,
            CatMatchBand::Fuzzy85_94 => 3000,
            CatMatchBand::NoMatch => 5000,
            default => 0,
        },
        'discount_percent' => $b->defaultDiscountPercent(),
    ])->all();

    $this->post(route('translation.projects.cat-analyses.store', $this->project), [
        'project_target_id' => $this->target->id,
        'name' => 'Initial Analysis',
        'tool' => CatTool::Manual->value,
        'bands' => $bands,
    ])->assertRedirect(route('translation.projects.show', $this->project));

    $this->assertDatabaseHas('cat_analyses', [
        'project_target_id' => $this->target->id,
        'name' => 'Initial Analysis',
        'tool' => CatTool::Manual->value,
    ]);

    $analysis = CatAnalysis::where('project_target_id', $this->target->id)->first();
    expect($analysis)->not->toBeNull();
    expect($analysis->bands)->toHaveCount(8);

    $exactBand = $analysis->bands->firstWhere('band', CatMatchBand::ExactMatch);
    expect($exactBand->words)->toBe(2000);
});

it('validates required fields on store', function () {
    $this->post(route('translation.projects.cat-analyses.store', $this->project), [])
        ->assertSessionHasErrors(['project_target_id', 'name', 'tool', 'bands']);
});

it('validates words are non-negative', function () {
    $bands = collect(CatMatchBand::cases())->map(fn ($b) => [
        'band' => $b->value,
        'words' => -1,
        'discount_percent' => 0,
    ])->all();

    $this->post(route('translation.projects.cat-analyses.store', $this->project), [
        'project_target_id' => $this->target->id,
        'name' => 'Bad Analysis',
        'tool' => 'manual',
        'bands' => $bands,
    ])->assertSessionHasErrors(['bands.0.words']);
});

// ── Show ─────────────────────────────────────────────────────────

it('can view a CAT analysis', function () {
    $analysis = CatAnalysis::create([
        'business_id' => $this->business->id,
        'project_target_id' => $this->target->id,
        'name' => 'Test Analysis',
        'tool' => CatTool::Manual->value,
    ]);

    foreach (CatMatchBand::cases() as $band) {
        CatAnalysisBand::create([
            'cat_analysis_id' => $analysis->id,
            'band' => $band->value,
            'words' => $band === CatMatchBand::NoMatch ? 5000 : 0,
            'discount_percent' => $band->defaultDiscountPercent(),
        ]);
    }

    $this->get(route('translation.projects.cat-analyses.show', [$this->project, $analysis]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('translation/projects/cat-analyses/show')
            ->has('analysis')
            ->where('totalWords', 5000)
            ->where('weightedWords', '5000.00')
        );
});

// ── Delete ───────────────────────────────────────────────────────

it('can delete a CAT analysis', function () {
    $analysis = CatAnalysis::create([
        'business_id' => $this->business->id,
        'project_target_id' => $this->target->id,
        'name' => 'To Delete',
        'tool' => CatTool::Manual->value,
    ]);

    $this->delete(route('translation.projects.cat-analyses.destroy', [$this->project, $analysis]))
        ->assertRedirect(route('translation.projects.show', $this->project));

    $this->assertDatabaseMissing('cat_analyses', ['id' => $analysis->id]);
});

// ── Weighted Word Count ──────────────────────────────────────────

it('calculates weighted word count correctly', function () {
    $analysis = CatAnalysis::create([
        'business_id' => $this->business->id,
        'project_target_id' => $this->target->id,
        'name' => 'Weighted Test',
        'tool' => CatTool::Manual->value,
    ]);

    // Context match: 1000 words × 0% charge = 0 effective
    CatAnalysisBand::create(['cat_analysis_id' => $analysis->id, 'band' => 'context_match', 'words' => 1000, 'discount_percent' => 100]);
    // Exact match: 2000 words × 30% charge (70% discount) = 600 effective
    CatAnalysisBand::create(['cat_analysis_id' => $analysis->id, 'band' => 'exact_match', 'words' => 2000, 'discount_percent' => 70]);
    // No match: 4000 words × 100% charge (0% discount) = 4000 effective
    CatAnalysisBand::create(['cat_analysis_id' => $analysis->id, 'band' => 'no_match', 'words' => 4000, 'discount_percent' => 0]);

    $analysis->loadMissing('bands');

    expect($analysis->totalWords())->toBe(7000);
    // 0 + 600 + 4000 = 4600
    expect($analysis->weightedWords())->toBe('4600.00');
});

// ── Apply to Quote ───────────────────────────────────────────────

it('can apply CAT analysis to generate a quote', function () {
    $analysis = CatAnalysis::create([
        'business_id' => $this->business->id,
        'project_target_id' => $this->target->id,
        'name' => 'Quote Test',
        'tool' => CatTool::Manual->value,
    ]);

    // No match: 5000 words, no discount → 5000 effective words
    CatAnalysisBand::create(['cat_analysis_id' => $analysis->id, 'band' => 'no_match', 'words' => 5000, 'discount_percent' => 0]);
    // Exact match: 2000 words, 70% discount → 600 effective words
    CatAnalysisBand::create(['cat_analysis_id' => $analysis->id, 'band' => 'exact_match', 'words' => 2000, 'discount_percent' => 70]);
    // Zero bands for others
    foreach (CatMatchBand::cases() as $band) {
        if (! in_array($band->value, ['no_match', 'exact_match'])) {
            CatAnalysisBand::create(['cat_analysis_id' => $analysis->id, 'band' => $band->value, 'words' => 0, 'discount_percent' => $band->defaultDiscountPercent()]);
        }
    }

    $this->post(route('translation.projects.cat-analyses.apply-quote', [$this->project, $analysis]))
        ->assertRedirect();

    $this->project->refresh();
    expect($this->project->quote_id)->not->toBeNull();

    $quote = $this->project->quote;
    expect($quote)->not->toBeNull();
    // Should have 2 non-zero band lines (no_match + exact_match)
    expect($quote->lines)->toHaveCount(2);
});

it('prevents applying to quote if one already exists', function () {
    // First analysis generates a quote
    $firstAnalysis = CatAnalysis::create([
        'business_id' => $this->business->id,
        'project_target_id' => $this->target->id,
        'name' => 'First',
        'tool' => CatTool::Manual->value,
    ]);
    foreach (CatMatchBand::cases() as $band) {
        CatAnalysisBand::create(['cat_analysis_id' => $firstAnalysis->id, 'band' => $band->value, 'words' => 100, 'discount_percent' => $band->defaultDiscountPercent()]);
    }

    $this->post(route('translation.projects.cat-analyses.apply-quote', [$this->project, $firstAnalysis]));

    // Second analysis should fail to generate another quote
    $secondAnalysis = CatAnalysis::create([
        'business_id' => $this->business->id,
        'project_target_id' => $this->target->id,
        'name' => 'Second',
        'tool' => CatTool::Manual->value,
    ]);
    foreach (CatMatchBand::cases() as $band) {
        CatAnalysisBand::create(['cat_analysis_id' => $secondAnalysis->id, 'band' => $band->value, 'words' => 100, 'discount_percent' => $band->defaultDiscountPercent()]);
    }

    $this->post(route('translation.projects.cat-analyses.apply-quote', [$this->project, $secondAnalysis]))
        ->assertRedirect()
        ->assertSessionHas('error');
});

// ── Apply to PO ──────────────────────────────────────────────────

it('can apply CAT analysis to generate purchase orders', function () {
    $translator = Contact::factory()->create([
        'business_id' => $this->business->id,
        'type' => 'supplier',
    ]);

    ProjectAssignment::create([
        'project_target_id' => $this->target->id,
        'contact_id' => $translator->id,
        'role' => ProjectAssignmentRole::TRANSLATOR->value,
        'rate' => '0.0500',
    ]);

    $analysis = CatAnalysis::create([
        'business_id' => $this->business->id,
        'project_target_id' => $this->target->id,
        'name' => 'PO Test',
        'tool' => CatTool::Manual->value,
    ]);

    // 4000 no-match words → 4000 weighted words
    CatAnalysisBand::create(['cat_analysis_id' => $analysis->id, 'band' => 'no_match', 'words' => 4000, 'discount_percent' => 0]);
    foreach (CatMatchBand::cases() as $band) {
        if ($band->value !== 'no_match') {
            CatAnalysisBand::create(['cat_analysis_id' => $analysis->id, 'band' => $band->value, 'words' => 0, 'discount_percent' => $band->defaultDiscountPercent()]);
        }
    }

    $this->post(route('translation.projects.cat-analyses.apply-po', [$this->project, $analysis]))
        ->assertRedirect()
        ->assertSessionHas('success');

    $this->assertDatabaseHas('invoices', [
        'contact_id' => $translator->id,
        'type' => 'purchase_order',
    ]);
});
