<?php

namespace App\Policies;

use App\Domain\Shared\Enums\BusinessRole;
use App\Models\Business;
use App\Models\User;

class BusinessPolicy
{
    /** Any authenticated user can list their own businesses. */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /** Any member of the business can view it. */
    public function view(User $user, Business $business): bool
    {
        return $user->belongsToBusiness($business);
    }

    /** Any authenticated user can create a new business. */
    public function create(User $user): bool
    {
        return true;
    }

    /** Admin or above can update business settings. */
    public function update(User $user, Business $business): bool
    {
        return $user->canManageIn($business);
    }

    /** Only the owner can delete a business. */
    public function delete(User $user, Business $business): bool
    {
        return $user->roleIn($business) === BusinessRole::OWNER;
    }

    /** Only the owner can switch another member's role. */
    public function manageMembers(User $user, Business $business): bool
    {
        return $user->roleIn($business) === BusinessRole::OWNER;
    }
}
