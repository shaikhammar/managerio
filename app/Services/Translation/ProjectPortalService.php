<?php

namespace App\Services\Translation;

use App\Models\Project;
use Illuminate\Support\Facades\URL;

class ProjectPortalService
{
    public function generatePortalUrl(Project $project): string
    {
        return URL::signedRoute('portal.projects.show', ['project' => $project->id]);
    }
}
