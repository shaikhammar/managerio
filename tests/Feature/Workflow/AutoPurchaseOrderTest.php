<?php

use App\Domain\Sales\Enums\InvoiceType;
use App\Domain\Translation\Enums\ProjectStatus;
use App\Events\ProjectMovedToInProgress;
use App\Models\Contact;
use App\Models\Invoice;
use App\Models\Language;
use App\Models\LanguagePair;
use App\Models\Project;
use App\Models\ProjectAssignment;
use App\Models\ProjectTarget;
use App\Models\ServiceType;
use App\Models\User;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->business = setupBusiness($this->user);
});

it('fires ProjectMovedToInProgress event when project transitions to in_progress', function () {
    Event::fake();

    $project = Project::factory()->create([
        'business_id' => $this->business->id,
        'status' => ProjectStatus::NEW,
    ]);

    $this->actingAs($this->user)
        ->post("/translation/projects/{$project->id}/status", ['status' => 'in_progress'])
        ->assertRedirect();

    Event::assertDispatched(ProjectMovedToInProgress::class, fn ($e) => $e->project->id === $project->id);
});

it('does not fire ProjectMovedToInProgress for other status transitions', function () {
    Event::fake();

    $project = Project::factory()->create([
        'business_id' => $this->business->id,
        'status' => ProjectStatus::IN_PROGRESS,
    ]);

    $this->actingAs($this->user)
        ->post("/translation/projects/{$project->id}/status", ['status' => 'review'])
        ->assertRedirect();

    Event::assertNotDispatched(ProjectMovedToInProgress::class);
});

it('auto-creates purchase orders for all unassigned translators when project moves to in_progress', function () {
    $srcLang = Language::factory()->create(['business_id' => $this->business->id]);
    $tgtLang = Language::factory()->create(['business_id' => $this->business->id]);
    $lp = LanguagePair::factory()->create([
        'business_id' => $this->business->id,
        'source_language_id' => $srcLang->id,
        'target_language_id' => $tgtLang->id,
    ]);
    $st = ServiceType::factory()->create(['business_id' => $this->business->id]);
    $translator = Contact::factory()->supplier()->create(['business_id' => $this->business->id]);

    $project = Project::factory()->create([
        'business_id' => $this->business->id,
        'source_language_id' => $srcLang->id,
        'service_type_id' => $st->id,
        'status' => ProjectStatus::NEW,
    ]);

    $target = ProjectTarget::factory()->create([
        'project_id' => $project->id,
        'language_pair_id' => $lp->id,
    ]);

    ProjectAssignment::factory()->create([
        'project_target_id' => $target->id,
        'contact_id' => $translator->id,
        'purchase_order_id' => null,
    ]);

    $this->actingAs($this->user)
        ->post("/translation/projects/{$project->id}/status", ['status' => 'in_progress'])
        ->assertRedirect();

    expect($target->assignments()->whereNotNull('purchase_order_id')->count())->toBe(1);
});

it('does not duplicate purchase orders for already-assigned translators', function () {
    $srcLang = Language::factory()->create(['business_id' => $this->business->id]);
    $tgtLang = Language::factory()->create(['business_id' => $this->business->id]);
    $lp = LanguagePair::factory()->create([
        'business_id' => $this->business->id,
        'source_language_id' => $srcLang->id,
        'target_language_id' => $tgtLang->id,
    ]);
    $st = ServiceType::factory()->create(['business_id' => $this->business->id]);
    $translator = Contact::factory()->supplier()->create(['business_id' => $this->business->id]);

    $project = Project::factory()->create([
        'business_id' => $this->business->id,
        'source_language_id' => $srcLang->id,
        'service_type_id' => $st->id,
        'status' => ProjectStatus::NEW,
    ]);

    $target = ProjectTarget::factory()->create([
        'project_id' => $project->id,
        'language_pair_id' => $lp->id,
    ]);

    $existingPo = Invoice::factory()->create([
        'business_id' => $this->business->id,
        'type' => InvoiceType::PURCHASE_ORDER,
    ]);

    ProjectAssignment::factory()->create([
        'project_target_id' => $target->id,
        'contact_id' => $translator->id,
        'purchase_order_id' => $existingPo->id,
    ]);

    $this->actingAs($this->user)
        ->post("/translation/projects/{$project->id}/status", ['status' => 'in_progress'])
        ->assertRedirect();

    expect($target->assignments()->first()->purchase_order_id)->toBe($existingPo->id);
});

it('does not error when project has no assignments', function () {
    $project = Project::factory()->create([
        'business_id' => $this->business->id,
        'status' => ProjectStatus::NEW,
    ]);

    $this->actingAs($this->user)
        ->post("/translation/projects/{$project->id}/status", ['status' => 'in_progress'])
        ->assertRedirect();

    expect(true)->toBeTrue();
});
