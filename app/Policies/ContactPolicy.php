<?php

namespace App\Policies;

use App\Models\Contact;
use App\Models\User;

class ContactPolicy
{
    /** Any business member can browse contacts. */
    public function viewAny(User $user): bool
    {
        $business = $user->currentBusiness();

        return $business !== null && $user->belongsToBusiness($business);
    }

    /** Any business member can view a contact. */
    public function view(User $user, Contact $contact): bool
    {
        return $user->belongsToBusiness($contact->business);
    }

    /** Editor or above can create contacts. */
    public function create(User $user): bool
    {
        $business = $user->currentBusiness();

        return $business !== null && $user->canEditIn($business);
    }

    /** Editor or above can update contacts. */
    public function update(User $user, Contact $contact): bool
    {
        return $user->canEditIn($contact->business);
    }

    /** Admin or above can delete contacts. */
    public function delete(User $user, Contact $contact): bool
    {
        return $user->canManageIn($contact->business);
    }
}
