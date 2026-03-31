<?php

use App\Domain\Translation\Enums\BillingUnit;
use App\Domain\Translation\Enums\ProjectStatus;
use App\Models\Contact;
use App\Models\Language;
use App\Models\LanguagePair;
use App\Models\Project;
use App\Models\ProjectAssignment;
use App\Models\ProjectTarget;
use App\Models\ServiceType;
use App\Models\TranslatorProfile;
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

    $this->sourceLanguage = $src;

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

    $this->translator = Contact::factory()->create([
        'business_id' => $this->business->id,
        'name' => 'Jane Translator',
        'type' => 'supplier',
    ]);
});

// ── Board ─────────────────────────────────────────────────────────────

it('can load the project board', function () {
    Project::factory()->create([
        'business_id' => $this->business->id,
        'contact_id' => $this->client->id,
        'source_language_id' => $this->sourceLanguage->id,
        'service_type_id' => $this->serviceType->id,
        'name' => 'Website Localisation',
        'status' => ProjectStatus::IN_PROGRESS,
    ]);

    $this->get(route('translation.projects.board'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('translation/projects/board')
            ->has('board')
        );
});

it('board excludes closed projects from its columns', function () {
    Project::factory()->create([
        'business_id' => $this->business->id,
        'contact_id' => $this->client->id,
        'source_language_id' => $this->sourceLanguage->id,
        'service_type_id' => $this->serviceType->id,
        'name' => 'Closed Project',
        'status' => ProjectStatus::CLOSED,
    ]);

    $response = $this->get(route('translation.projects.board'));
    $response->assertOk();

    $board = $response->original->getData()['page']['props']['board'];
    expect(array_keys($board))->not->toContain('closed');
});

it('board groups projects by status', function () {
    Project::factory()->create([
        'business_id' => $this->business->id,
        'contact_id' => $this->client->id,
        'source_language_id' => $this->sourceLanguage->id,
        'service_type_id' => $this->serviceType->id,
        'name' => 'Active Project',
        'status' => ProjectStatus::IN_PROGRESS,
    ]);

    $this->get(route('translation.projects.board'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('board.in_progress.projects', 1)
        );
});

// ── Calendar ─────────────────────────────────────────────────────────

it('can load the project calendar', function () {
    $this->get(route('translation.projects.calendar'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('translation/projects/calendar')
            ->has('projects')
            ->has('year')
            ->has('month')
        );
});

it('calendar returns only projects with deadlines in the requested month', function () {
    Project::factory()->create([
        'business_id' => $this->business->id,
        'contact_id' => $this->client->id,
        'source_language_id' => $this->sourceLanguage->id,
        'service_type_id' => $this->serviceType->id,
        'name' => 'March Project',
        'status' => ProjectStatus::IN_PROGRESS,
        'deadline' => '2026-03-15',
    ]);
    Project::factory()->create([
        'business_id' => $this->business->id,
        'contact_id' => $this->client->id,
        'source_language_id' => $this->sourceLanguage->id,
        'service_type_id' => $this->serviceType->id,
        'name' => 'April Project',
        'status' => ProjectStatus::IN_PROGRESS,
        'deadline' => '2026-04-10',
    ]);

    $this->get(route('translation.projects.calendar', ['year' => 2026, 'month' => 3]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('projects', 1)
            ->where('projects.0.name', 'March Project')
        );
});

it('calendar marks overdue in-progress projects', function () {
    Project::factory()->create([
        'business_id' => $this->business->id,
        'contact_id' => $this->client->id,
        'source_language_id' => $this->sourceLanguage->id,
        'service_type_id' => $this->serviceType->id,
        'name' => 'Past Project',
        'status' => ProjectStatus::IN_PROGRESS,
        'deadline' => '2025-01-01',
    ]);

    $this->get(route('translation.projects.calendar', ['year' => 2025, 'month' => 1]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('projects', 1)
            ->where('projects.0.is_overdue', true)
        );
});

it('calendar does not mark completed projects as overdue', function () {
    Project::factory()->create([
        'business_id' => $this->business->id,
        'contact_id' => $this->client->id,
        'source_language_id' => $this->sourceLanguage->id,
        'service_type_id' => $this->serviceType->id,
        'name' => 'Done Project',
        'status' => ProjectStatus::COMPLETED,
        'deadline' => '2025-01-01',
    ]);

    $this->get(route('translation.projects.calendar', ['year' => 2025, 'month' => 1]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('projects.0.is_overdue', false)
        );
});

// ── Capacity ─────────────────────────────────────────────────────────

it('can load the capacity planning page', function () {
    $this->get(route('translation.projects.capacity'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('translation/projects/capacity')
            ->has('translators')
        );
});

it('capacity shows translator pipeline word counts and utilization', function () {
    TranslatorProfile::factory()->create([
        'business_id' => $this->business->id,
        'contact_id' => $this->translator->id,
        'weekly_capacity' => 5000,
    ]);

    $project = Project::factory()->create([
        'business_id' => $this->business->id,
        'contact_id' => $this->client->id,
        'source_language_id' => $this->sourceLanguage->id,
        'service_type_id' => $this->serviceType->id,
        'status' => ProjectStatus::IN_PROGRESS,
    ]);

    $target = ProjectTarget::factory()->create([
        'project_id' => $project->id,
        'language_pair_id' => $this->languagePair->id,
        'word_count' => 2000,
    ]);

    ProjectAssignment::factory()->create([
        'project_target_id' => $target->id,
        'contact_id' => $this->translator->id,
    ]);

    $this->get(route('translation.projects.capacity'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('translators', 1)
            ->where('translators.0.pipeline_words', 2000)
            ->where('translators.0.weekly_capacity', 5000)
            ->where('translators.0.utilization_percent', 40)
        );
});

it('capacity excludes delivered and closed projects from pipeline', function () {
    TranslatorProfile::factory()->create([
        'business_id' => $this->business->id,
        'contact_id' => $this->translator->id,
        'weekly_capacity' => 5000,
    ]);

    $deliveredProject = Project::factory()->create([
        'business_id' => $this->business->id,
        'contact_id' => $this->client->id,
        'source_language_id' => $this->sourceLanguage->id,
        'service_type_id' => $this->serviceType->id,
        'status' => ProjectStatus::DELIVERED,
    ]);

    $target = ProjectTarget::factory()->create([
        'project_id' => $deliveredProject->id,
        'language_pair_id' => $this->languagePair->id,
        'word_count' => 3000,
    ]);

    ProjectAssignment::factory()->create([
        'project_target_id' => $target->id,
        'contact_id' => $this->translator->id,
    ]);

    $this->get(route('translation.projects.capacity'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('translators.0.pipeline_words', 0)
            ->where('translators.0.utilization_percent', 0)
        );
});

// ── Project index enhanced filters ───────────────────────────────────

it('can filter projects by client', function () {
    $otherClient = Contact::factory()->create([
        'business_id' => $this->business->id,
        'name' => 'Other Corp',
        'type' => 'customer',
    ]);

    Project::factory()->create([
        'business_id' => $this->business->id,
        'contact_id' => $this->client->id,
        'source_language_id' => $this->sourceLanguage->id,
        'service_type_id' => $this->serviceType->id,
        'name' => 'Acme Project',
    ]);
    Project::factory()->create([
        'business_id' => $this->business->id,
        'contact_id' => $otherClient->id,
        'source_language_id' => $this->sourceLanguage->id,
        'service_type_id' => $this->serviceType->id,
        'name' => 'Other Project',
    ]);

    $this->get(route('translation.projects.index', ['client_id' => $this->client->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('projects.data', 1)
            ->where('projects.data.0.name', 'Acme Project')
        );
});

it('can filter projects by deadline range', function () {
    Project::factory()->create([
        'business_id' => $this->business->id,
        'contact_id' => $this->client->id,
        'source_language_id' => $this->sourceLanguage->id,
        'service_type_id' => $this->serviceType->id,
        'name' => 'Early Project',
        'deadline' => '2026-01-10',
    ]);
    Project::factory()->create([
        'business_id' => $this->business->id,
        'contact_id' => $this->client->id,
        'source_language_id' => $this->sourceLanguage->id,
        'service_type_id' => $this->serviceType->id,
        'name' => 'Late Project',
        'deadline' => '2026-06-01',
    ]);

    $this->get(route('translation.projects.index', ['deadline_from' => '2026-05-01', 'deadline_to' => '2026-12-31']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('projects.data', 1)
            ->where('projects.data.0.name', 'Late Project')
        );
});

it('can filter projects by language pair', function () {
    $otherSrc = Language::factory()->create(['business_id' => $this->business->id, 'code' => 'FR', 'name' => 'French']);
    $otherTgt = Language::factory()->create(['business_id' => $this->business->id, 'code' => 'IT', 'name' => 'Italian']);
    $otherPair = LanguagePair::factory()->create([
        'business_id' => $this->business->id,
        'source_language_id' => $otherSrc->id,
        'target_language_id' => $otherTgt->id,
    ]);

    $projectA = Project::factory()->create([
        'business_id' => $this->business->id,
        'contact_id' => $this->client->id,
        'source_language_id' => $this->sourceLanguage->id,
        'service_type_id' => $this->serviceType->id,
        'name' => 'EN-DE Project',
    ]);
    ProjectTarget::factory()->create([
        'project_id' => $projectA->id,
        'language_pair_id' => $this->languagePair->id,
    ]);

    $projectB = Project::factory()->create([
        'business_id' => $this->business->id,
        'contact_id' => $this->client->id,
        'source_language_id' => $otherSrc->id,
        'service_type_id' => $this->serviceType->id,
        'name' => 'FR-IT Project',
    ]);
    ProjectTarget::factory()->create([
        'project_id' => $projectB->id,
        'language_pair_id' => $otherPair->id,
    ]);

    $this->get(route('translation.projects.index', ['language_pair_id' => $this->languagePair->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('projects.data', 1)
            ->where('projects.data.0.name', 'EN-DE Project')
        );
});
