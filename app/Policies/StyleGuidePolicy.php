<?php

namespace App\Policies;

use App\Models\StyleGuide;
use App\Models\User;

class StyleGuidePolicy
{
    /** Any business member can browse style guides. */
    public function viewAny(User $user): bool
    {
        $business = $user->currentBusiness();

        return $business !== null && $user->belongsToBusiness($business);
    }

    /** Any business member can view a style guide. */
    public function view(User $user, StyleGuide $styleGuide): bool
    {
        return $user->belongsToBusiness($styleGuide->business);
    }

    /** Editor or above can create style guides. */
    public function create(User $user): bool
    {
        $business = $user->currentBusiness();

        return $business !== null && $user->canEditIn($business);
    }

    /** Editor or above can update style guides. */
    public function update(User $user, StyleGuide $styleGuide): bool
    {
        return $user->canEditIn($styleGuide->business);
    }

    /** Admin or above can delete style guides. */
    public function delete(User $user, StyleGuide $styleGuide): bool
    {
        return $user->canManageIn($styleGuide->business);
    }
}
