<?php

namespace App\Http\Controllers\Translation;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\StyleGuide;
use App\Models\TermBase;
use App\Models\TranslationMemory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProjectResourceController extends Controller
{
    public function attachTranslationMemory(Request $request, Project $project, TranslationMemory $translationMemory): RedirectResponse
    {
        $this->authorize('update', $project);

        $project->translationMemories()->syncWithoutDetaching([$translationMemory->id]);

        return back()->with('success', 'Translation memory attached to project.');
    }

    public function detachTranslationMemory(Request $request, Project $project, TranslationMemory $translationMemory): RedirectResponse
    {
        $this->authorize('update', $project);

        $project->translationMemories()->detach($translationMemory->id);

        return back()->with('success', 'Translation memory detached from project.');
    }

    public function attachTermBase(Request $request, Project $project, TermBase $termBase): RedirectResponse
    {
        $this->authorize('update', $project);

        $project->termBases()->syncWithoutDetaching([$termBase->id]);

        return back()->with('success', 'Term base attached to project.');
    }

    public function detachTermBase(Request $request, Project $project, TermBase $termBase): RedirectResponse
    {
        $this->authorize('update', $project);

        $project->termBases()->detach($termBase->id);

        return back()->with('success', 'Term base detached from project.');
    }

    public function attachStyleGuide(Request $request, Project $project, StyleGuide $styleGuide): RedirectResponse
    {
        $this->authorize('update', $project);

        $project->styleGuides()->syncWithoutDetaching([$styleGuide->id]);

        return back()->with('success', 'Style guide attached to project.');
    }

    public function detachStyleGuide(Request $request, Project $project, StyleGuide $styleGuide): RedirectResponse
    {
        $this->authorize('update', $project);

        $project->styleGuides()->detach($styleGuide->id);

        return back()->with('success', 'Style guide detached from project.');
    }
}
