<?php

namespace App\Http\Controllers\Translation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Translation\TranslationMemoryRequest;
use App\Models\Contact;
use App\Models\Language;
use App\Models\TranslationMemory;
use App\Services\Translation\TranslationMemoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TranslationMemoryController extends Controller
{
    public function __construct(private TranslationMemoryService $translationMemoryService) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', TranslationMemory::class);

        $translationMemories = TranslationMemory::query()
            ->with(['contact', 'sourceLanguage', 'targetLanguage'])
            ->when($request->search, function ($q, $search) {
                $lower = strtolower($search);
                $q->whereRaw('LOWER(name) LIKE ?', ["%{$lower}%"]);
            })
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('translation/translation-memories/index', [
            'translationMemories' => $translationMemories,
            'filters' => $request->only('search'),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', TranslationMemory::class);

        return Inertia::render('translation/translation-memories/create', $this->formData());
    }

    public function store(TranslationMemoryRequest $request): RedirectResponse
    {
        $this->authorize('create', TranslationMemory::class);

        $business = $request->user()->currentBusiness();
        $this->translationMemoryService->create($business, $request->validated());

        return redirect()
            ->route('translation.translation-memories.index')
            ->with('success', 'Translation memory created successfully.');
    }

    public function edit(TranslationMemory $translationMemory): Response
    {
        $this->authorize('update', $translationMemory);

        return Inertia::render('translation/translation-memories/edit', [
            'translationMemory' => $translationMemory->load(['contact', 'sourceLanguage', 'targetLanguage']),
            ...$this->formData(),
        ]);
    }

    public function update(TranslationMemoryRequest $request, TranslationMemory $translationMemory): RedirectResponse
    {
        $this->authorize('update', $translationMemory);

        $this->translationMemoryService->update($translationMemory, $request->validated());

        return redirect()
            ->route('translation.translation-memories.index')
            ->with('success', 'Translation memory updated successfully.');
    }

    public function destroy(TranslationMemory $translationMemory): RedirectResponse
    {
        $this->authorize('delete', $translationMemory);

        $this->translationMemoryService->delete($translationMemory);

        return redirect()
            ->route('translation.translation-memories.index')
            ->with('success', 'Translation memory deleted.');
    }

    /** @return array<string, mixed> */
    private function formData(): array
    {
        return [
            'languages' => Language::active()->orderBy('name')->get(['id', 'code', 'name']),
            'customers' => Contact::active()->customers()->orderBy('name')->get(['id', 'name']),
        ];
    }
}
