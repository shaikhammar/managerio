<?php

namespace App\Policies;

use App\Models\TranslationMemory;
use App\Models\User;

class TranslationMemoryPolicy
{
    /** Any business member can browse translation memories. */
    public function viewAny(User $user): bool
    {
        $business = $user->currentBusiness();

        return $business !== null && $user->belongsToBusiness($business);
    }

    /** Any business member can view a translation memory. */
    public function view(User $user, TranslationMemory $translationMemory): bool
    {
        return $user->belongsToBusiness($translationMemory->business);
    }

    /** Editor or above can create translation memories. */
    public function create(User $user): bool
    {
        $business = $user->currentBusiness();

        return $business !== null && $user->canEditIn($business);
    }

    /** Editor or above can update translation memories. */
    public function update(User $user, TranslationMemory $translationMemory): bool
    {
        return $user->canEditIn($translationMemory->business);
    }

    /** Admin or above can delete translation memories. */
    public function delete(User $user, TranslationMemory $translationMemory): bool
    {
        return $user->canManageIn($translationMemory->business);
    }
}
