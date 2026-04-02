<?php

namespace App\Http\Controllers\Portal;

use App\Models\Project;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ProjectPortalController
{
    public function show(Project $project): InertiaResponse
    {
        $project = Project::withoutGlobalScopes()
            ->with(['contact', 'sourceLanguage', 'serviceType', 'targets.languagePair.targetLanguage', 'business'])
            ->findOrFail($project->id);

        return Inertia::render('portal/project-status', [
            'project' => $project,
            'business' => $project->business,
        ]);
    }
}
