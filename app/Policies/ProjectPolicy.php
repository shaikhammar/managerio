<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    /** Any business member can browse projects. */
    public function viewAny(User $user): bool
    {
        $business = $user->currentBusiness();

        return $business !== null && $user->belongsToBusiness($business);
    }

    /** Any business member can view a project. */
    public function view(User $user, Project $project): bool
    {
        return $user->belongsToBusiness($project->business);
    }

    /** Editor or above can create projects. */
    public function create(User $user): bool
    {
        $business = $user->currentBusiness();

        return $business !== null && $user->canEditIn($business);
    }

    /** Editor or above can update projects. */
    public function update(User $user, Project $project): bool
    {
        return $user->canEditIn($project->business);
    }

    /** Admin or above can delete projects. */
    public function delete(User $user, Project $project): bool
    {
        return $user->canManageIn($project->business);
    }
}
