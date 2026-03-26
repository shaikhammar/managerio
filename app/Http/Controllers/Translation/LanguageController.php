<?php

namespace App\Http\Controllers\Translation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Translation\LanguageRequest;
use App\Models\Language;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LanguageController extends Controller
{
    public function index(Request $request): Response
    {
        $languages = Language::query()
            ->when($request->search, fn ($q, $search) => $q->where('name', 'ilike', "%{$search}%")
                ->orWhere('code', 'ilike', "%{$search}%"))
            ->orderBy('name')
            ->paginate(25);

        return Inertia::render('translation/languages/index', [
            'languages' => $languages,
            'filters' => $request->only('search'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('translation/languages/create');
    }

    public function store(LanguageRequest $request): RedirectResponse
    {
        Language::create($request->validated());

        return redirect()->route('translation.languages.index')
            ->with('success', 'Language created successfully.');
    }

    public function edit(Language $language): Response
    {
        return Inertia::render('translation/languages/create', [
            'language' => $language,
        ]);
    }

    public function update(LanguageRequest $request, Language $language): RedirectResponse
    {
        $language->update($request->validated());

        return redirect()->route('translation.languages.index')
            ->with('success', 'Language updated successfully.');
    }

    public function destroy(Language $language): RedirectResponse
    {
        $language->delete();

        return redirect()->route('translation.languages.index')
            ->with('success', 'Language deleted successfully.');
    }
}
