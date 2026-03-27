<?php

namespace App\Http\Controllers\Translation;

use App\Domain\Translation\Enums\CatMatchBand;
use App\Domain\Translation\Enums\CatTool;
use App\Http\Controllers\Controller;
use App\Http\Requests\Translation\CatAnalysisImportRequest;
use App\Http\Requests\Translation\CatAnalysisRequest;
use App\Models\CatAnalysis;
use App\Models\Project;
use App\Models\ProjectTarget;
use App\Services\Translation\CatAnalysisService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CatAnalysisController extends Controller
{
    public function __construct(private CatAnalysisService $catAnalysisService) {}

    public function create(Project $project): Response
    {
        $this->authorize('update', $project);

        $project->loadMissing(['targets.languagePair.sourceLanguage', 'targets.languagePair.targetLanguage', 'targets.serviceType']);

        return Inertia::render('translation/projects/cat-analyses/create', [
            'project' => $project,
            'targets' => $project->targets,
            'bands' => collect(CatMatchBand::cases())->map(fn ($b) => [
                'value' => $b->value,
                'label' => $b->label(),
                'defaultDiscount' => $b->defaultDiscountPercent(),
            ]),
            'tools' => collect(CatTool::cases())->map(fn ($t) => ['value' => $t->value, 'label' => $t->label()]),
        ]);
    }

    public function store(CatAnalysisRequest $request, Project $project): RedirectResponse
    {
        $this->authorize('update', $project);

        $target = ProjectTarget::findOrFail($request->validated('project_target_id'));

        try {
            $this->catAnalysisService->create($target, $request->validated());
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('translation.projects.show', $project)
            ->with('success', 'CAT analysis saved.');
    }

    public function import(CatAnalysisImportRequest $request, Project $project): RedirectResponse
    {
        $this->authorize('update', $project);

        $target = ProjectTarget::findOrFail($request->validated('project_target_id'));

        try {
            $this->catAnalysisService->importFromFile(
                $target,
                $request->file('file'),
                $request->validated('tool')
            );
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('translation.projects.show', $project)
            ->with('success', 'CAT analysis imported successfully.');
    }

    public function show(Project $project, CatAnalysis $catAnalysis): Response
    {
        $this->authorize('view', $project);

        $catAnalysis->loadMissing(['bands', 'projectTarget.languagePair.sourceLanguage', 'projectTarget.languagePair.targetLanguage', 'projectTarget.serviceType']);

        return Inertia::render('translation/projects/cat-analyses/show', [
            'project' => $project,
            'analysis' => $catAnalysis,
            'totalWords' => $catAnalysis->totalWords(),
            'weightedWords' => $catAnalysis->weightedWords(),
        ]);
    }

    public function destroy(Project $project, CatAnalysis $catAnalysis): RedirectResponse
    {
        $this->authorize('update', $project);

        $this->catAnalysisService->delete($catAnalysis);

        return redirect()
            ->route('translation.projects.show', $project)
            ->with('success', 'CAT analysis deleted.');
    }

    public function applyToQuote(Project $project, CatAnalysis $catAnalysis): RedirectResponse
    {
        $this->authorize('update', $project);

        $catAnalysis->loadMissing(['bands', 'projectTarget']);

        try {
            $quote = $this->catAnalysisService->applyToQuote($catAnalysis, $project);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('sales.quotes.show', $quote)
            ->with('success', 'Quote generated from CAT analysis.');
    }

    public function applyToPurchaseOrders(Project $project, CatAnalysis $catAnalysis): RedirectResponse
    {
        $this->authorize('update', $project);

        $catAnalysis->loadMissing(['bands', 'projectTarget.assignments.contact']);
        $project->loadMissing(['targets.languagePair.sourceLanguage', 'targets.languagePair.targetLanguage', 'targets.serviceType']);

        try {
            $orders = $this->catAnalysisService->applyToPurchaseOrders($catAnalysis, $project);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        $count = $orders->count();

        if ($count === 0) {
            return back()->with('error', 'No assignments without purchase orders found for this target.');
        }

        return back()->with('success', "{$count} purchase order(s) generated from CAT analysis.");
    }
}
