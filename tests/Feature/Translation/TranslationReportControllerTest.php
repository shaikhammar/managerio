<?php

use App\Domain\Sales\Enums\InvoiceStatus;
use App\Domain\Sales\Enums\InvoiceType;
use App\Domain\Translation\Enums\BillingUnit;
use App\Domain\Translation\Enums\ProjectAssignmentRole;
use App\Domain\Translation\Enums\ProjectStatus;
use App\Models\Contact;
use App\Models\Invoice;
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

it('can load the translation reports index', function () {
    $this->get('/translation/reports')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('translation/reports/index'));
});

// ── Project Profitability ────────────────────────────────────────────

it('can load the project profitability report', function () {
    $project = Project::factory()->create([
        'business_id' => $this->business->id,
        'contact_id' => $this->client->id,
        'source_language_id' => $this->sourceLanguage->id,
        'service_type_id' => $this->serviceType->id,
        'deadline' => now()->toDateString(),
        'status' => ProjectStatus::INVOICED->value,
    ]);

    ProjectTarget::factory()->create([
        'project_id' => $project->id,
        'language_pair_id' => $this->languagePair->id,
        'service_type_id' => $this->serviceType->id,
        'word_count' => 1000,
        'unit_price' => '0.12',
    ]);

    $this->get('/translation/reports/project-profitability?start_date='.now()->startOfYear()->toDateString().'&end_date='.now()->addDay()->toDateString())
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('translation/reports/project-profitability')
            ->has('report.projects')
            ->has('report.totals')
        );
});

it('calculates margin correctly using invoice total when linked', function () {
    $invoice = Invoice::factory()->create([
        'business_id' => $this->business->id,
        'contact_id' => $this->client->id,
        'type' => InvoiceType::INVOICE->value,
        'status' => InvoiceStatus::PAID->value,
        'total' => '1500.00',
    ]);

    $purchaseOrder = Invoice::factory()->create([
        'business_id' => $this->business->id,
        'contact_id' => $this->translator->id,
        'type' => InvoiceType::PURCHASE_ORDER->value,
        'status' => InvoiceStatus::SENT->value,
        'total' => '600.00',
    ]);

    $project = Project::factory()->create([
        'business_id' => $this->business->id,
        'contact_id' => $this->client->id,
        'source_language_id' => $this->sourceLanguage->id,
        'service_type_id' => $this->serviceType->id,
        'invoice_id' => $invoice->id,
        'deadline' => now()->toDateString(),
        'status' => ProjectStatus::INVOICED->value,
    ]);

    $target = ProjectTarget::factory()->create([
        'project_id' => $project->id,
        'language_pair_id' => $this->languagePair->id,
        'service_type_id' => $this->serviceType->id,
        'word_count' => 1000,
        'unit_price' => '0.10',
    ]);

    ProjectAssignment::factory()->create([
        'project_target_id' => $target->id,
        'contact_id' => $this->translator->id,
        'role' => ProjectAssignmentRole::TRANSLATOR->value,
        'rate' => '0.06',
        'purchase_order_id' => $purchaseOrder->id,
    ]);

    $this->get('/translation/reports/project-profitability?start_date='.now()->startOfYear()->toDateString().'&end_date='.now()->addDay()->toDateString())
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('translation/reports/project-profitability')
            ->has('report.projects', 1)
            ->where('report.projects.0.revenue', 1500)
            ->where('report.projects.0.cost', 600)
            ->where('report.projects.0.margin', 900)
            ->where('report.projects.0.margin_pct', 60)
        );
});

it('excludes projects from other businesses on profitability report', function () {
    $otherUser = User::factory()->create();
    $otherBusiness = app(BusinessSetupService::class)->createBusiness($otherUser, ['name' => 'Other', 'currency_code' => 'USD', 'country' => 'US']);

    $otherSrc = Language::factory()->create(['business_id' => $otherBusiness->id, 'code' => 'FR', 'name' => 'French']);
    $otherSt = ServiceType::factory()->create(['business_id' => $otherBusiness->id, 'name' => 'Translation', 'code' => 'tr', 'default_unit' => BillingUnit::Word]);

    Project::factory()->create([
        'business_id' => $otherBusiness->id,
        'source_language_id' => $otherSrc->id,
        'service_type_id' => $otherSt->id,
        'deadline' => now()->toDateString(),
        'status' => ProjectStatus::NEW->value,
    ]);

    $this->get('/translation/reports/project-profitability?start_date='.now()->startOfYear()->toDateString().'&end_date='.now()->addDay()->toDateString())
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('translation/reports/project-profitability')
            ->has('report.projects', 0)
        );
});

// ── Revenue by Language Pair ─────────────────────────────────────────

it('can load the revenue by language pair report', function () {
    $project = Project::factory()->create([
        'business_id' => $this->business->id,
        'source_language_id' => $this->sourceLanguage->id,
        'service_type_id' => $this->serviceType->id,
        'deadline' => now()->toDateString(),
        'status' => ProjectStatus::INVOICED->value,
    ]);

    ProjectTarget::factory()->create([
        'project_id' => $project->id,
        'language_pair_id' => $this->languagePair->id,
        'service_type_id' => $this->serviceType->id,
        'word_count' => 2000,
        'unit_price' => '0.10',
    ]);

    $this->get('/translation/reports/revenue-language-pair?start_date='.now()->startOfYear()->toDateString().'&end_date='.now()->addDay()->toDateString())
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('translation/reports/revenue-language-pair')
            ->has('report.rows', 1)
            ->where('report.rows.0.language_pair', 'EN → DE')
            ->where('report.rows.0.revenue', 200)
            ->where('report.totals.revenue', 200)
        );
});

// ── Revenue by Service Type ──────────────────────────────────────────

it('can load the revenue by service type report', function () {
    $project = Project::factory()->create([
        'business_id' => $this->business->id,
        'source_language_id' => $this->sourceLanguage->id,
        'service_type_id' => $this->serviceType->id,
        'deadline' => now()->toDateString(),
        'status' => ProjectStatus::INVOICED->value,
    ]);

    ProjectTarget::factory()->create([
        'project_id' => $project->id,
        'language_pair_id' => $this->languagePair->id,
        'service_type_id' => $this->serviceType->id,
        'word_count' => 500,
        'unit_price' => '0.20',
    ]);

    $this->get('/translation/reports/revenue-service-type?start_date='.now()->startOfYear()->toDateString().'&end_date='.now()->addDay()->toDateString())
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('translation/reports/revenue-service-type')
            ->has('report.rows', 1)
            ->where('report.rows.0.service_type', 'Translation')
            ->where('report.rows.0.revenue', 100)
        );
});

// ── Revenue by Client ────────────────────────────────────────────────

it('can load the revenue by client report', function () {
    $project = Project::factory()->create([
        'business_id' => $this->business->id,
        'contact_id' => $this->client->id,
        'source_language_id' => $this->sourceLanguage->id,
        'service_type_id' => $this->serviceType->id,
        'deadline' => now()->toDateString(),
        'status' => ProjectStatus::INVOICED->value,
    ]);

    ProjectTarget::factory()->create([
        'project_id' => $project->id,
        'language_pair_id' => $this->languagePair->id,
        'service_type_id' => $this->serviceType->id,
        'word_count' => 1000,
        'unit_price' => '0.15',
    ]);

    $this->get('/translation/reports/revenue-client?start_date='.now()->startOfYear()->toDateString().'&end_date='.now()->addDay()->toDateString())
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('translation/reports/revenue-client')
            ->has('report.rows', 1)
            ->where('report.rows.0.client', 'Acme Corp')
            ->where('report.rows.0.revenue', 150)
        );
});

// ── Translator Utilisation ───────────────────────────────────────────

it('can load the translator utilisation report', function () {
    $project = Project::factory()->create([
        'business_id' => $this->business->id,
        'source_language_id' => $this->sourceLanguage->id,
        'service_type_id' => $this->serviceType->id,
        'deadline' => now()->toDateString(),
        'status' => ProjectStatus::IN_PROGRESS->value,
    ]);

    $target = ProjectTarget::factory()->create([
        'project_id' => $project->id,
        'language_pair_id' => $this->languagePair->id,
        'service_type_id' => $this->serviceType->id,
        'word_count' => 2000,
        'unit_price' => '0.12',
    ]);

    ProjectAssignment::factory()->create([
        'project_target_id' => $target->id,
        'contact_id' => $this->translator->id,
        'role' => ProjectAssignmentRole::TRANSLATOR->value,
        'rate' => '0.05',
        'purchase_order_id' => null,
    ]);

    $this->get('/translation/reports/translator-utilisation?start_date='.now()->startOfYear()->toDateString().'&end_date='.now()->addDay()->toDateString())
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('translation/reports/translator-utilisation')
            ->has('report.rows', 1)
            ->where('report.rows.0.translator', 'Jane Translator')
            ->where('report.rows.0.word_count', 2000)
        );
});

// ── Average Margin ───────────────────────────────────────────────────

it('can load the average margin report', function () {
    $project = Project::factory()->create([
        'business_id' => $this->business->id,
        'source_language_id' => $this->sourceLanguage->id,
        'service_type_id' => $this->serviceType->id,
        'deadline' => now()->toDateString(),
        'status' => ProjectStatus::CLOSED->value,
    ]);

    ProjectTarget::factory()->create([
        'project_id' => $project->id,
        'language_pair_id' => $this->languagePair->id,
        'service_type_id' => $this->serviceType->id,
        'word_count' => 1000,
        'unit_price' => '0.10',
    ]);

    $this->get('/translation/reports/average-margin?start_date='.now()->startOfYear()->toDateString().'&end_date='.now()->addDay()->toDateString())
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('translation/reports/average-margin')
            ->where('report.project_count', 1)
            ->where('report.total_revenue', 100)
        );
});

// ── Delivery Performance ─────────────────────────────────────────────

it('can load the delivery performance report', function () {
    Project::factory()->create([
        'business_id' => $this->business->id,
        'source_language_id' => $this->sourceLanguage->id,
        'service_type_id' => $this->serviceType->id,
        'deadline' => now()->toDateString(),
        'status' => ProjectStatus::DELIVERED->value,
    ]);

    $this->get('/translation/reports/delivery-performance?start_date='.now()->startOfYear()->toDateString().'&end_date='.now()->addDay()->toDateString())
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('translation/reports/delivery-performance')
            ->has('report.summary')
            ->has('report.completed')
            ->has('report.overdue')
        );
});

it('correctly flags overdue active projects', function () {
    Project::factory()->create([
        'business_id' => $this->business->id,
        'source_language_id' => $this->sourceLanguage->id,
        'service_type_id' => $this->serviceType->id,
        'deadline' => now()->subDays(5)->toDateString(),
        'status' => ProjectStatus::IN_PROGRESS->value,
    ]);

    $this->get('/translation/reports/delivery-performance?start_date='.now()->subYear()->toDateString().'&end_date='.now()->toDateString())
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('translation/reports/delivery-performance')
            ->has('report.overdue', 1)
            ->where('report.overdue.0.days_overdue', 5)
        );
});

// ── Pipeline ─────────────────────────────────────────────────────────

it('can load the pipeline report', function () {
    $project = Project::factory()->create([
        'business_id' => $this->business->id,
        'contact_id' => $this->client->id,
        'source_language_id' => $this->sourceLanguage->id,
        'service_type_id' => $this->serviceType->id,
        'deadline' => now()->addDays(10)->toDateString(),
        'status' => ProjectStatus::IN_PROGRESS->value,
    ]);

    ProjectTarget::factory()->create([
        'project_id' => $project->id,
        'language_pair_id' => $this->languagePair->id,
        'service_type_id' => $this->serviceType->id,
        'word_count' => 1000,
        'unit_price' => '0.12',
    ]);

    $this->get('/translation/reports/pipeline')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('translation/reports/pipeline')
            ->has('report.rows', 1)
            ->where('report.rows.0.client', 'Acme Corp')
            ->where('report.rows.0.expected_revenue', 120)
            ->where('report.totals.project_count', 1)
        );
});

it('excludes closed projects from pipeline', function () {
    Project::factory()->create([
        'business_id' => $this->business->id,
        'source_language_id' => $this->sourceLanguage->id,
        'service_type_id' => $this->serviceType->id,
        'status' => ProjectStatus::CLOSED->value,
    ]);

    $this->get('/translation/reports/pipeline')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('translation/reports/pipeline')
            ->has('report.rows', 0)
        );
});

it('requires authentication for all translation reports', function () {
    auth()->logout();

    $routes = [
        '/translation/reports',
        '/translation/reports/project-profitability',
        '/translation/reports/revenue-language-pair',
        '/translation/reports/revenue-service-type',
        '/translation/reports/revenue-client',
        '/translation/reports/translator-utilisation',
        '/translation/reports/average-margin',
        '/translation/reports/delivery-performance',
        '/translation/reports/pipeline',
    ];

    foreach ($routes as $route) {
        $this->get($route)->assertRedirect('/login');
    }
});
