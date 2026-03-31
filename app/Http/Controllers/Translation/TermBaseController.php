<?php

namespace App\Http\Controllers\Translation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Translation\TermBaseRequest;
use App\Models\Contact;
use App\Models\TermBase;
use App\Services\Translation\TermBaseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TermBaseController extends Controller
{
    public function __construct(private TermBaseService $termBaseService) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', TermBase::class);

        $termBases = TermBase::query()
            ->with(['contact'])
            ->when($request->search, function ($q, $search) {
                $lower = strtolower($search);
                $q->whereRaw('LOWER(name) LIKE ?', ["%{$lower}%"]);
            })
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('translation/term-bases/index', [
            'termBases' => $termBases,
            'filters' => $request->only('search'),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', TermBase::class);

        return Inertia::render('translation/term-bases/create', $this->formData());
    }

    public function store(TermBaseRequest $request): RedirectResponse
    {
        $this->authorize('create', TermBase::class);

        $business = $request->user()->currentBusiness();
        $this->termBaseService->create($business, $request->validated());

        return redirect()
            ->route('translation.term-bases.index')
            ->with('success', 'Term base created successfully.');
    }

    public function edit(TermBase $termBase): Response
    {
        $this->authorize('update', $termBase);

        return Inertia::render('translation/term-bases/edit', [
            'termBase' => $termBase->load(['contact']),
            ...$this->formData(),
        ]);
    }

    public function update(TermBaseRequest $request, TermBase $termBase): RedirectResponse
    {
        $this->authorize('update', $termBase);

        $this->termBaseService->update($termBase, $request->validated());

        return redirect()
            ->route('translation.term-bases.index')
            ->with('success', 'Term base updated successfully.');
    }

    public function destroy(TermBase $termBase): RedirectResponse
    {
        $this->authorize('delete', $termBase);

        $this->termBaseService->delete($termBase);

        return redirect()
            ->route('translation.term-bases.index')
            ->with('success', 'Term base deleted.');
    }

    /** @return array<string, mixed> */
    private function formData(): array
    {
        return [
            'customers' => Contact::active()->customers()->orderBy('name')->get(['id', 'name']),
            'subjectFields' => ['General', 'Legal', 'Medical', 'Technical', 'Marketing', 'Financial', 'IT', 'Life Sciences'],
        ];
    }
}
