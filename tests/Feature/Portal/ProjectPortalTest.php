<?php

use App\Models\Contact;
use App\Models\Language;
use App\Models\Project;
use App\Models\ServiceType;
use App\Models\User;
use App\Services\Translation\ProjectPortalService;
use Illuminate\Support\Facades\URL;

beforeEach(function () {
    $user = User::factory()->create();
    $this->business = setupBusiness($user);
    $contact = Contact::factory()->create(['business_id' => $this->business->id]);
    $language = Language::factory()->create();
    $serviceType = ServiceType::factory()->create(['business_id' => $this->business->id]);

    $this->project = Project::factory()->create([
        'business_id' => $this->business->id,
        'contact_id' => $contact->id,
        'source_language_id' => $language->id,
        'service_type_id' => $serviceType->id,
        'name' => 'Acme Annual Report',
        'status' => 'in_progress',
        'deadline' => now()->addDays(7)->format('Y-m-d'),
    ]);

    $this->service = app(ProjectPortalService::class);
});

it('returns 403 with invalid signature on project portal', function () {
    $this->get('/portal/projects/'.$this->project->id)->assertStatus(403);
});

it('shows the project status page with a valid signature', function () {
    $url = URL::signedRoute('portal.projects.show', ['project' => $this->project->id]);

    $this->get($url)
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('portal/project-status')
            ->where('project.name', 'Acme Annual Report')
        );
});

it('generates a valid signed portal URL for a project', function () {
    $url = $this->service->generatePortalUrl($this->project);

    expect($url)->toContain('/portal/projects/'.$this->project->id);
});
