<?php

namespace App\Http\Controllers\Translation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Translation\StyleGuideRequest;
use App\Models\Contact;
use App\Models\StyleGuide;
use App\Services\Translation\StyleGuideService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StyleGuideController extends Controller
{
    public function __construct(private StyleGuideService $styleGuideService) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', StyleGuide::class);

        $styleGuides = StyleGuide::query()
            ->with(['contact'])
            ->when($request->search, function ($q, $search) {
                $lower = strtolower($search);
                $q->whereRaw('LOWER(name) LIKE ?', ["%{$lower}%"]);
            })
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('translation/style-guides/index', [
            'styleGuides' => $styleGuides,
            'filters' => $request->only('search'),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', StyleGuide::class);

        return Inertia::render('translation/style-guides/create', $this->formData());
    }

    public function store(StyleGuideRequest $request): RedirectResponse
    {
        $this->authorize('create', StyleGuide::class);

        $business = $request->user()->currentBusiness();
        $this->styleGuideService->create($business, $request->validated(), $request->file('file'));

        return redirect()
            ->route('translation.style-guides.index')
            ->with('success', 'Style guide created successfully.');
    }

    public function edit(StyleGuide $styleGuide): Response
    {
        $this->authorize('update', $styleGuide);

        return Inertia::render('translation/style-guides/edit', [
            'styleGuide' => $styleGuide->load(['contact']),
            ...$this->formData(),
        ]);
    }

    public function update(StyleGuideRequest $request, StyleGuide $styleGuide): RedirectResponse
    {
        $this->authorize('update', $styleGuide);

        $this->styleGuideService->update($styleGuide, $request->validated(), $request->file('file'));

        return redirect()
            ->route('translation.style-guides.index')
            ->with('success', 'Style guide updated successfully.');
    }

    public function destroy(StyleGuide $styleGuide): RedirectResponse
    {
        $this->authorize('delete', $styleGuide);

        $this->styleGuideService->delete($styleGuide);

        return redirect()
            ->route('translation.style-guides.index')
            ->with('success', 'Style guide deleted.');
    }

    /** @return array<string, mixed> */
    private function formData(): array
    {
        return [
            'customers' => Contact::active()->customers()->orderBy('name')->get(['id', 'name']),
        ];
    }
}
