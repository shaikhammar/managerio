<?php

namespace App\Http\Controllers\Translation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Translation\LanguagePairRequest;
use App\Models\Language;
use App\Models\LanguagePair;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LanguagePairController extends Controller
{
    public function index(Request $request): Response
    {
        $pairs = LanguagePair::query()
            ->with(['sourceLanguage', 'targetLanguage'])
            ->when($request->search, function ($q, $search) {
                $lower = strtolower($search);
                $q->whereHas('sourceLanguage', fn ($l) => $l->whereRaw('LOWER(name) LIKE ?', ["%{$lower}%"])
                    ->orWhereRaw('LOWER(code) LIKE ?', ["%{$lower}%"]))
                    ->orWhereHas('targetLanguage', fn ($l) => $l->whereRaw('LOWER(name) LIKE ?', ["%{$lower}%"])
                        ->orWhereRaw('LOWER(code) LIKE ?', ["%{$lower}%"]));
            })
            ->orderBy('id')
            ->paginate(25);

        return Inertia::render('translation/language-pairs/index', [
            'pairs' => $pairs,
            'filters' => $request->only('search'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('translation/language-pairs/create', [
            'languages' => Language::query()->active()->orderBy('name')->get(['id', 'code', 'name']),
        ]);
    }

    public function store(LanguagePairRequest $request): RedirectResponse
    {
        LanguagePair::create($request->validated());

        return redirect()->route('translation.language-pairs.index')
            ->with('success', 'Language pair created successfully.');
    }

    public function edit(LanguagePair $languagePair): Response
    {
        return Inertia::render('translation/language-pairs/create', [
            'pair' => $languagePair->load(['sourceLanguage', 'targetLanguage']),
            'languages' => Language::query()->active()->orderBy('name')->get(['id', 'code', 'name']),
        ]);
    }

    public function update(LanguagePairRequest $request, LanguagePair $languagePair): RedirectResponse
    {
        $languagePair->update($request->validated());

        return redirect()->route('translation.language-pairs.index')
            ->with('success', 'Language pair updated successfully.');
    }

    public function destroy(LanguagePair $languagePair): RedirectResponse
    {
        $languagePair->delete();

        return redirect()->route('translation.language-pairs.index')
            ->with('success', 'Language pair deleted successfully.');
    }
}
