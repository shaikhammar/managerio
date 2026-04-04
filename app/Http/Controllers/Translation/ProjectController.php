<?php

namespace App\Http\Controllers\Translation;

use App\Domain\Translation\Enums\ProjectAssignmentRole;
use App\Domain\Translation\Enums\ProjectFileType;
use App\Domain\Translation\Enums\ProjectStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Translation\ProjectFileRequest;
use App\Http\Requests\Translation\ProjectRequest;
use App\Http\Requests\Translation\ProjectStatusRequest;
use App\Models\Contact;
use App\Models\Language;
use App\Models\LanguagePair;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\ServiceType;
use App\Models\StyleGuide;
use App\Models\TermBase;
use App\Models\TranslationMemory;
use App\Services\Translation\ProjectPortalService;
use App\Services\Translation\ProjectService;
use App\Services\Translation\TranslatorSuggestionService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    public function __construct(
        private ProjectService $projectService,
        private ProjectPortalService $projectPortalService,
        private TranslatorSuggestionService $translatorSuggestionService,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Project::class);

        $projects = Project::query()
            ->with(['contact', 'sourceLanguage', 'serviceType'])
            ->when($request->search, fn ($q, $search) => $q->search($search))
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->when($request->client_id, fn ($q, $id) => $q->where('contact_id', $id))
            ->when($request->service_type_id, fn ($q, $id) => $q->where('service_type_id', $id))
            ->when($request->language_pair_id, fn ($q, $id) => $q->forLanguagePair((int) $id))
            ->when(
                $request->deadline_from || $request->deadline_to,
                fn ($q) => $q->forDeadlineRange($request->deadline_from, $request->deadline_to)
            )
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('translation/projects/index', [
            'projects' => $projects,
            'filters' => $request->only('search', 'status', 'client_id', 'service_type_id', 'language_pair_id', 'deadline_from', 'deadline_to'),
            'statuses' => collect(ProjectStatus::cases())->map(fn ($s) => ['value' => $s->value, 'label' => $s->label(), 'color' => $s->color()]),
            'customers' => Contact::active()->customers()->orderBy('name')->get(['id', 'name']),
            'serviceTypes' => ServiceType::active()->orderBy('name')->get(['id', 'name']),
            'languagePairs' => LanguagePair::with(['sourceLanguage', 'targetLanguage'])->active()->get(['id', 'source_language_id', 'target_language_id']),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Project::class);

        return Inertia::render('translation/projects/create', $this->formData());
    }

    public function store(ProjectRequest $request): RedirectResponse
    {
        $this->authorize('create', Project::class);

        $business = $request->user()->currentBusiness();
        $project = $this->projectService->create($business, $request->validated());

        return redirect()
            ->route('translation.projects.show', $project)
            ->with('success', 'Project created successfully.');
    }

    public function show(Project $project): Response
    {
        $this->authorize('view', $project);

        $project->loadMissing([
            'contact',
            'sourceLanguage',
            'serviceType',
            'targets.languagePair.sourceLanguage',
            'targets.languagePair.targetLanguage',
            'targets.serviceType',
            'targets.assignments.contact',
            'targets.assignments.purchaseOrder',
            'targets.catAnalyses.bands',
            'files',
            'quote',
            'invoice',
            'translationMemories',
            'termBases',
            'styleGuides',
        ]);

        $attachedTmIds = $project->translationMemories->pluck('id');
        $attachedTbIds = $project->termBases->pluck('id');
        $attachedSgIds = $project->styleGuides->pluck('id');

        return Inertia::render('translation/projects/show', [
            'project' => $project,
            'statuses' => collect(ProjectStatus::cases())->map(fn ($s) => ['value' => $s->value, 'label' => $s->label(), 'color' => $s->color()]),
            'transitionableStatuses' => collect($project->status->transitionableFrom($project->status))
                ->map(fn ($s) => ['value' => $s->value, 'label' => $s->label()]),
            'fileTypes' => collect(ProjectFileType::cases())->map(fn ($t) => ['value' => $t->value, 'label' => $t->label()]),
            'availableTranslationMemories' => TranslationMemory::whereNotIn('id', $attachedTmIds)->orderBy('name')->get(['id', 'name']),
            'availableTermBases' => TermBase::whereNotIn('id', $attachedTbIds)->orderBy('name')->get(['id', 'name']),
            'availableStyleGuides' => StyleGuide::whereNotIn('id', $attachedSgIds)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function edit(Project $project): Response
    {
        $this->authorize('update', $project);

        $project->loadMissing([
            'targets.assignments',
        ]);

        return Inertia::render('translation/projects/edit', [
            'project' => $project,
            ...$this->formData(),
        ]);
    }

    public function update(ProjectRequest $request, Project $project): RedirectResponse
    {
        $this->authorize('update', $project);

        try {
            $this->projectService->update($project, $request->validated());
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('translation.projects.show', $project)
            ->with('success', 'Project updated successfully.');
    }

    public function destroy(Project $project): RedirectResponse
    {
        $this->authorize('delete', $project);

        $project->targets()->each(function ($target) {
            $target->assignments()->delete();
            $target->delete();
        });

        $project->files->each(fn ($file) => $this->projectService->deleteFile($file));
        $project->delete();

        return redirect()
            ->route('translation.projects.index')
            ->with('success', 'Project deleted.');
    }

    public function updateStatus(ProjectStatusRequest $request, Project $project): RedirectResponse
    {
        $this->authorize('update', $project);

        try {
            $newStatus = ProjectStatus::from($request->validated('status'));
            $this->projectService->updateStatus($project, $newStatus);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Project status updated to {$project->fresh()->status->label()}.");
    }

    public function generateQuote(Project $project): RedirectResponse
    {
        $this->authorize('update', $project);

        try {
            $quote = $this->projectService->generateQuote($project);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('sales.quotes.show', $quote)
            ->with('success', 'Quote generated from project.');
    }

    public function generateInvoice(Project $project): RedirectResponse
    {
        $this->authorize('update', $project);

        try {
            $invoice = $this->projectService->generateInvoice($project);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('sales.invoices.show', $invoice)
            ->with('success', 'Invoice generated from project.');
    }

    public function generatePurchaseOrders(Project $project): RedirectResponse
    {
        $this->authorize('update', $project);

        try {
            $orders = $this->projectService->generatePurchaseOrders($project);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        $count = $orders->count();

        if ($count === 0) {
            return back()->with('error', 'No assignments without purchase orders found.');
        }

        return back()->with('success', "{$count} purchase order(s) generated.");
    }

    public function storeFile(ProjectFileRequest $request, Project $project): RedirectResponse
    {
        $this->authorize('update', $project);

        $this->projectService->storeFile(
            $project,
            $request->file('file'),
            $request->validated('type')
        );

        return back()->with('success', 'File uploaded successfully.');
    }

    public function portalLink(Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        return response()->json(['url' => $this->projectPortalService->generatePortalUrl($project)]);
    }

    public function suggestTranslators(Request $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $validated = $request->validate([
            'language_pair_id' => ['required', 'integer'],
            'service_type_id' => ['required', 'integer'],
        ]);

        $business = $request->user()->currentBusiness();

        $suggestions = $this->translatorSuggestionService->suggest(
            $business,
            (int) $validated['language_pair_id'],
            (int) $validated['service_type_id'],
        );

        return response()->json($suggestions);
    }

    public function destroyFile(Project $project, ProjectFile $projectFile): RedirectResponse
    {
        $this->authorize('update', $project);

        $this->projectService->deleteFile($projectFile);

        return back()->with('success', 'File deleted.');
    }

    /** @return array<string, mixed> */
    private function formData(): array
    {
        return [
            'customers' => Contact::active()->customers()->orderBy('name')->get(['id', 'name']),
            'suppliers' => Contact::active()->suppliers()->orderBy('name')->get(['id', 'name']),
            'languages' => Language::active()->orderBy('name')->get(['id', 'code', 'name']),
            'languagePairs' => LanguagePair::with(['sourceLanguage', 'targetLanguage'])->active()->get(),
            'serviceTypes' => ServiceType::active()->orderBy('name')->get(['id', 'name', 'default_unit']),
            'statuses' => collect(ProjectStatus::cases())->map(fn ($s) => ['value' => $s->value, 'label' => $s->label()]),
            'roles' => collect(ProjectAssignmentRole::cases())->map(fn ($r) => ['value' => $r->value, 'label' => $r->label()]),
        ];
    }
}
