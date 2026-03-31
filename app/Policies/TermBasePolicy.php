<?php

namespace App\Policies;

use App\Models\TermBase;
use App\Models\User;

class TermBasePolicy
{
    /** Any business member can browse term bases. */
    public function viewAny(User $user): bool
    {
        $business = $user->currentBusiness();

        return $business !== null && $user->belongsToBusiness($business);
    }

    /** Any business member can view a term base. */
    public function view(User $user, TermBase $termBase): bool
    {
        return $user->belongsToBusiness($termBase->business);
    }

    /** Editor or above can create term bases. */
    public function create(User $user): bool
    {
        $business = $user->currentBusiness();

        return $business !== null && $user->canEditIn($business);
    }

    /** Editor or above can update term bases. */
    public function update(User $user, TermBase $termBase): bool
    {
        return $user->canEditIn($termBase->business);
    }

    /** Admin or above can delete term bases. */
    public function delete(User $user, TermBase $termBase): bool
    {
        return $user->canManageIn($termBase->business);
    }
}
