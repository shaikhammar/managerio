<?php

use App\Domain\Translation\Enums\BillingUnit;
use App\Domain\Translation\Enums\ProjectAssignmentRole;
use App\Domain\Translation\Enums\ProjectStatus;
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

// ── Index ───────────────────────────────────────────────────────────

it('can load the projects index', function () {
    Project::factory()->create([
        'business_id' => $this->business->id,
        'contact_id' => $this->client->id,
        'source_language_id' => $this->sourceLanguage->id,
        'service_type_id' => $this->serviceType->id,
        'name' => 'Annual Report Translation',
    ]);

    $this->get(route('translation.projects.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('translation/projects/index')
            ->has('projects.data', 1)
            ->has('statuses')
        );
});

it('can filter projects by status', function () {
    Project::factory()->create([
        'business_id' => $this->business->id,
        'contact_id' => $this->client->id,
        'source_language_id' => $this->sourceLanguage->id,
        'service_type_id' => $this->serviceType->id,
        'status' => ProjectStatus::IN_PROGRESS,
    ]);

    Project::factory()->create([
        'business_id' => $this->business->id,
        'contact_id' => $this->client->id,
        'source_language_id' => $this->sourceLanguage->id,
        'service_type_id' => $this->serviceType->id,
        'status' => ProjectStatus::NEW,
    ]);

    $this->get(route('translation.projects.index', ['status' => 'in_progress']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('projects.data', 1));
});

it('does not show other business projects', function () {
    $otherUser = User::factory()->create();
    $otherBusiness = app(BusinessSetupService::class)->createBusiness($otherUser, [
        'name' => 'Other Agency',
        'currency_code' => 'USD',
        'country' => 'US',
    ]);

    $otherLang = Language::factory()->create(['business_id' => $otherBusiness->id, 'code' => 'FR']);
    $otherServiceType = ServiceType::factory()->create(['business_id' => $otherBusiness->id]);
    $otherContact = Contact::factory()->create(['business_id' => $otherBusiness->id, 'type' => 'customer']);

    Project::factory()->create([
        'business_id' => $otherBusiness->id,
        'contact_id' => $otherContact->id,
        'source_language_id' => $otherLang->id,
        'service_type_id' => $otherServiceType->id,
    ]);

    $this->get(route('translation.projects.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('projects.data', 0));
});

// ── Create / Store ───────────────────────────────────────────────────

it('can load the create project page', function () {
    $this->get(route('translation.projects.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('translation/projects/create')
            ->has('customers')
            ->has('suppliers')
            ->has('languages')
            ->has('languagePairs')
            ->has('serviceTypes')
            ->has('roles')
        );
});

it('can create a project without targets', function () {
    $this->post(route('translation.projects.store'), [
        'name' => 'Brand Brochure Translation',
        'contact_id' => $this->client->id,
        'source_language_id' => $this->sourceLanguage->id,
        'service_type_id' => $this->serviceType->id,
        'deadline' => now()->addDays(14)->format('Y-m-d'),
        'notes' => 'Urgent project',
    ])->assertRedirect();

    $this->assertDatabaseHas('projects', [
        'business_id' => $this->business->id,
        'name' => 'Brand Brochure Translation',
        'contact_id' => $this->client->id,
        'status' => 'new',
    ]);
});

it('auto-generates a project reference number', function () {
    $this->post(route('translation.projects.store'), [
        'name' => 'Test Project',
        'contact_id' => $this->client->id,
        'source_language_id' => $this->sourceLanguage->id,
        'service_type_id' => $this->serviceType->id,
    ])->assertRedirect();

    $project = Project::where('business_id', $this->business->id)->first();

    expect($project->reference)->not->toBeNull();
});

it('can create a project with targets and assignments', function () {
    $this->post(route('translation.projects.store'), [
        'name' => 'Multi-target Project',
        'contact_id' => $this->client->id,
        'source_language_id' => $this->sourceLanguage->id,
        'service_type_id' => $this->serviceType->id,
        'targets' => [
            [
                'language_pair_id' => $this->languagePair->id,
                'word_count' => 5000,
                'unit_price' => 0.12,
                'assignments' => [
                    [
                        'contact_id' => $this->translator->id,
                        'role' => ProjectAssignmentRole::TRANSLATOR->value,
                        'rate' => 0.07,
                    ],
                ],
            ],
        ],
    ])->assertRedirect();

    $project = Project::where('business_id', $this->business->id)->first();

    expect($project->targets)->toHaveCount(1);
    expect($project->targets->first()->assignments)->toHaveCount(1);
    expect($project->targets->first()->assignments->first()->role)->toBe(ProjectAssignmentRole::TRANSLATOR);
});

it('validates required project fields', function () {
    $this->post(route('translation.projects.store'), [])
        ->assertSessionHasErrors(['name', 'contact_id', 'source_language_id', 'service_type_id']);
});

// ── Show ─────────────────────────────────────────────────────────────

it('can view a project', function () {
    $project = Project::factory()->create([
        'business_id' => $this->business->id,
        'contact_id' => $this->client->id,
        'source_language_id' => $this->sourceLanguage->id,
        'service_type_id' => $this->serviceType->id,
    ]);

    $this->get(route('translation.projects.show', $project))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('translation/projects/show')
            ->where('project.id', $project->id)
        );
});

it('cannot view a project from another business', function () {
    $otherUser = User::factory()->create();
    $otherBusiness = app(BusinessSetupService::class)->createBusiness($otherUser, [
        'name' => 'Other',
        'currency_code' => 'USD',
        'country' => 'US',
    ]);
    $otherLang = Language::factory()->create(['business_id' => $otherBusiness->id]);
    $otherST = ServiceType::factory()->create(['business_id' => $otherBusiness->id]);
    $otherContact = Contact::factory()->create(['business_id' => $otherBusiness->id, 'type' => 'customer']);

    $project = Project::factory()->create([
        'business_id' => $otherBusiness->id,
        'contact_id' => $otherContact->id,
        'source_language_id' => $otherLang->id,
        'service_type_id' => $otherST->id,
    ]);

    // BelongsToBusiness scope hides records from other businesses, returning 404
    $this->get(route('translation.projects.show', $project))
        ->assertNotFound();
});

// ── Edit / Update ────────────────────────────────────────────────────

it('can load the edit project page', function () {
    $project = Project::factory()->create([
        'business_id' => $this->business->id,
        'contact_id' => $this->client->id,
        'source_language_id' => $this->sourceLanguage->id,
        'service_type_id' => $this->serviceType->id,
    ]);

    $this->get(route('translation.projects.edit', $project))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('translation/projects/edit')
            ->where('project.id', $project->id)
        );
});

it('can update a project', function () {
    $project = Project::factory()->create([
        'business_id' => $this->business->id,
        'contact_id' => $this->client->id,
        'source_language_id' => $this->sourceLanguage->id,
        'service_type_id' => $this->serviceType->id,
        'name' => 'Old Name',
    ]);

    $this->put(route('translation.projects.update', $project), [
        'name' => 'Updated Name',
        'contact_id' => $this->client->id,
        'source_language_id' => $this->sourceLanguage->id,
        'service_type_id' => $this->serviceType->id,
    ])->assertRedirect(route('translation.projects.show', $project));

    $this->assertDatabaseHas('projects', [
        'id' => $project->id,
        'name' => 'Updated Name',
    ]);
});

// ── Status Update ────────────────────────────────────────────────────

it('can update project status via valid transition', function () {
    $project = Project::factory()->create([
        'business_id' => $this->business->id,
        'contact_id' => $this->client->id,
        'source_language_id' => $this->sourceLanguage->id,
        'service_type_id' => $this->serviceType->id,
        'status' => ProjectStatus::NEW,
    ]);

    $this->post(route('translation.projects.status', $project), ['status' => 'in_progress'])
        ->assertRedirect();

    expect($project->fresh()->status)->toBe(ProjectStatus::IN_PROGRESS);
});

it('rejects invalid status transitions', function () {
    $project = Project::factory()->create([
        'business_id' => $this->business->id,
        'contact_id' => $this->client->id,
        'source_language_id' => $this->sourceLanguage->id,
        'service_type_id' => $this->serviceType->id,
        'status' => ProjectStatus::NEW,
    ]);

    $this->post(route('translation.projects.status', $project), ['status' => 'invoiced'])
        ->assertRedirect();

    expect($project->fresh()->status)->toBe(ProjectStatus::NEW);
});

// ── Generate Quote ───────────────────────────────────────────────────

it('can generate a quote from a project', function () {
    $project = Project::factory()->create([
        'business_id' => $this->business->id,
        'contact_id' => $this->client->id,
        'source_language_id' => $this->sourceLanguage->id,
        'service_type_id' => $this->serviceType->id,
    ]);

    ProjectTarget::factory()->create([
        'project_id' => $project->id,
        'language_pair_id' => $this->languagePair->id,
        'word_count' => 5000,
        'unit_price' => '0.1200',
    ]);

    $this->post(route('translation.projects.generate-quote', $project))
        ->assertRedirect();

    expect($project->fresh()->quote_id)->not->toBeNull();
    $this->assertDatabaseHas('invoices', [
        'business_id' => $this->business->id,
        'contact_id' => $this->client->id,
        'type' => 'quote',
    ]);
});

it('cannot generate a second quote when one already exists', function () {
    $project = Project::factory()->create([
        'business_id' => $this->business->id,
        'contact_id' => $this->client->id,
        'source_language_id' => $this->sourceLanguage->id,
        'service_type_id' => $this->serviceType->id,
    ]);

    // Generate first quote
    $this->post(route('translation.projects.generate-quote', $project));

    // Try to generate again
    $this->post(route('translation.projects.generate-quote', $project))
        ->assertSessionHas('error');
});

// ── Generate Invoice ─────────────────────────────────────────────────

it('can generate an invoice from a completed project', function () {
    $project = Project::factory()->create([
        'business_id' => $this->business->id,
        'contact_id' => $this->client->id,
        'source_language_id' => $this->sourceLanguage->id,
        'service_type_id' => $this->serviceType->id,
        'status' => ProjectStatus::COMPLETED,
    ]);

    ProjectTarget::factory()->create([
        'project_id' => $project->id,
        'language_pair_id' => $this->languagePair->id,
        'word_count' => 2000,
        'unit_price' => '0.1500',
    ]);

    $this->post(route('translation.projects.generate-invoice', $project))
        ->assertRedirect();

    expect($project->fresh()->invoice_id)->not->toBeNull();
    expect($project->fresh()->status)->toBe(ProjectStatus::INVOICED);
});

// ── Generate Purchase Orders ─────────────────────────────────────────

it('can generate purchase orders for assigned translators', function () {
    $project = Project::factory()->create([
        'business_id' => $this->business->id,
        'contact_id' => $this->client->id,
        'source_language_id' => $this->sourceLanguage->id,
        'service_type_id' => $this->serviceType->id,
    ]);

    $target = ProjectTarget::factory()->create([
        'project_id' => $project->id,
        'language_pair_id' => $this->languagePair->id,
        'word_count' => 3000,
    ]);

    $assignment = ProjectAssignment::factory()->create([
        'project_target_id' => $target->id,
        'contact_id' => $this->translator->id,
        'role' => ProjectAssignmentRole::TRANSLATOR,
        'rate' => '0.0700',
        'purchase_order_id' => null,
    ]);

    $this->post(route('translation.projects.generate-purchase-orders', $project))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($assignment->fresh()->purchase_order_id)->not->toBeNull();
    $this->assertDatabaseHas('invoices', [
        'business_id' => $this->business->id,
        'contact_id' => $this->translator->id,
        'type' => 'purchase_order',
    ]);
});

// ── Destroy ───────────────────────────────────────────────────────────

it('can delete a project', function () {
    $project = Project::factory()->create([
        'business_id' => $this->business->id,
        'contact_id' => $this->client->id,
        'source_language_id' => $this->sourceLanguage->id,
        'service_type_id' => $this->serviceType->id,
    ]);

    $this->delete(route('translation.projects.destroy', $project))
        ->assertRedirect(route('translation.projects.index'));

    $this->assertDatabaseMissing('projects', ['id' => $project->id]);
});
