<?php

use App\Domain\Translation\Enums\ProjectStatus;
use App\Mail\ProjectDeadlineAlertMail;
use App\Models\Contact;
use App\Models\Language;
use App\Models\LanguagePair;
use App\Models\Project;
use App\Models\ProjectAssignment;
use App\Models\ProjectTarget;
use App\Models\ServiceType;
use App\Models\User;
use App\Services\MailService;
use Illuminate\Mail\Mailer;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->business = setupBusiness($this->user);
    $this->business->update([
        'smtp_host' => 'smtp.test',
        'smtp_from_email' => 'agency@test.com',
    ]);

    $srcLang = Language::factory()->create(['business_id' => $this->business->id]);
    $tgtLang = Language::factory()->create(['business_id' => $this->business->id]);
    $this->lp = LanguagePair::factory()->create([
        'business_id' => $this->business->id,
        'source_language_id' => $srcLang->id,
        'target_language_id' => $tgtLang->id,
    ]);
    $this->st = ServiceType::factory()->create(['business_id' => $this->business->id]);
});

function projectDueInTwoDays(int $businessId, int $srcLangId, int $stId, ProjectStatus $status = ProjectStatus::IN_PROGRESS): Project
{
    return Project::factory()->create([
        'business_id' => $businessId,
        'source_language_id' => $srcLangId,
        'service_type_id' => $stId,
        'status' => $status,
        'deadline' => now()->addDays(2)->format('Y-m-d'),
        'deadline_alert_sent_at' => null,
    ]);
}

it('sends an alert to each assigned translator for a project due in 2 days', function () {
    $mockMailer = Mockery::mock(Mailer::class);
    $mockMailer->shouldReceive('to')->andReturnSelf();
    $mockMailer->shouldReceive('queue')->once()->with(Mockery::type(ProjectDeadlineAlertMail::class));
    $mockMailService = Mockery::mock(MailService::class);
    $mockMailService->shouldReceive('mailerFor')->once()->andReturn($mockMailer);
    app()->instance(MailService::class, $mockMailService);

    $translator = Contact::factory()->supplier()->create([
        'business_id' => $this->business->id,
        'email' => 'translator@example.com',
    ]);

    $project = projectDueInTwoDays($this->business->id, $this->lp->source_language_id, $this->st->id);
    $target = ProjectTarget::factory()->create(['project_id' => $project->id, 'language_pair_id' => $this->lp->id]);
    ProjectAssignment::factory()->create(['project_target_id' => $target->id, 'contact_id' => $translator->id]);

    $this->artisan('project-alerts:send-deadline')->assertSuccessful();
});

it('sets deadline_alert_sent_at after sending', function () {
    $mockMailer = Mockery::mock(Mailer::class);
    $mockMailer->shouldReceive('to')->andReturnSelf();
    $mockMailer->shouldReceive('queue')->once();
    $mockMailService = Mockery::mock(MailService::class);
    $mockMailService->shouldReceive('mailerFor')->once()->andReturn($mockMailer);
    app()->instance(MailService::class, $mockMailService);

    $translator = Contact::factory()->supplier()->create([
        'business_id' => $this->business->id,
        'email' => 'translator@example.com',
    ]);

    $project = projectDueInTwoDays($this->business->id, $this->lp->source_language_id, $this->st->id);
    $target = ProjectTarget::factory()->create(['project_id' => $project->id, 'language_pair_id' => $this->lp->id]);
    ProjectAssignment::factory()->create(['project_target_id' => $target->id, 'contact_id' => $translator->id]);

    $this->artisan('project-alerts:send-deadline')->assertSuccessful();

    expect($project->fresh()->deadline_alert_sent_at)->not->toBeNull();
});

it('skips a project that already has deadline_alert_sent_at set', function () {
    $mockMailService = Mockery::mock(MailService::class);
    $mockMailService->shouldNotReceive('mailerFor');
    app()->instance(MailService::class, $mockMailService);

    $translator = Contact::factory()->supplier()->create([
        'business_id' => $this->business->id,
        'email' => 'translator@example.com',
    ]);

    $project = Project::factory()->create([
        'business_id' => $this->business->id,
        'source_language_id' => $this->lp->source_language_id,
        'service_type_id' => $this->st->id,
        'status' => ProjectStatus::IN_PROGRESS,
        'deadline' => now()->addDays(2)->format('Y-m-d'),
        'deadline_alert_sent_at' => today(),
    ]);

    $target = ProjectTarget::factory()->create(['project_id' => $project->id, 'language_pair_id' => $this->lp->id]);
    ProjectAssignment::factory()->create(['project_target_id' => $target->id, 'contact_id' => $translator->id]);

    $this->artisan('project-alerts:send-deadline')->assertSuccessful();
});

it('skips a project whose deadline is not exactly 2 days away', function () {
    $mockMailService = Mockery::mock(MailService::class);
    $mockMailService->shouldNotReceive('mailerFor');
    app()->instance(MailService::class, $mockMailService);

    Project::factory()->create([
        'business_id' => $this->business->id,
        'source_language_id' => $this->lp->source_language_id,
        'service_type_id' => $this->st->id,
        'status' => ProjectStatus::IN_PROGRESS,
        'deadline' => now()->addDays(5)->format('Y-m-d'),
        'deadline_alert_sent_at' => null,
    ]);

    $this->artisan('project-alerts:send-deadline')->assertSuccessful();
});

it('skips completed and closed projects', function () {
    $mockMailService = Mockery::mock(MailService::class);
    $mockMailService->shouldNotReceive('mailerFor');
    app()->instance(MailService::class, $mockMailService);

    $translator = Contact::factory()->supplier()->create(['business_id' => $this->business->id, 'email' => 't@t.com']);

    foreach ([ProjectStatus::COMPLETED, ProjectStatus::CLOSED] as $status) {
        $project = projectDueInTwoDays($this->business->id, $this->lp->source_language_id, $this->st->id, $status);
        $target = ProjectTarget::factory()->create(['project_id' => $project->id, 'language_pair_id' => $this->lp->id]);
        ProjectAssignment::factory()->create(['project_target_id' => $target->id, 'contact_id' => $translator->id]);
    }

    $this->artisan('project-alerts:send-deadline')->assertSuccessful();
});

it('skips translators with no email address', function () {
    $mockMailService = Mockery::mock(MailService::class);
    $mockMailService->shouldNotReceive('mailerFor');
    app()->instance(MailService::class, $mockMailService);

    $noEmailTranslator = Contact::factory()->supplier()->create([
        'business_id' => $this->business->id,
        'email' => null,
    ]);

    $project = projectDueInTwoDays($this->business->id, $this->lp->source_language_id, $this->st->id);
    $target = ProjectTarget::factory()->create(['project_id' => $project->id, 'language_pair_id' => $this->lp->id]);
    ProjectAssignment::factory()->create(['project_target_id' => $target->id, 'contact_id' => $noEmailTranslator->id]);

    $this->artisan('project-alerts:send-deadline')->assertSuccessful();
});
