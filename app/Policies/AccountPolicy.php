<?php

namespace App\Policies;

use App\Models\Account;
use App\Models\User;

class AccountPolicy
{
    /** Any business member can browse the chart of accounts. */
    public function viewAny(User $user): bool
    {
        $business = $user->currentBusiness();

        return $business !== null && $user->belongsToBusiness($business);
    }

    /** Any business member can view an account. */
    public function view(User $user, Account $account): bool
    {
        return $user->belongsToBusiness($account->business);
    }

    /** Editor or above can create accounts. */
    public function create(User $user): bool
    {
        $business = $user->currentBusiness();

        return $business !== null && $user->canEditIn($business);
    }

    /** Editor or above can update accounts. */
    public function update(User $user, Account $account): bool
    {
        return $user->canEditIn($account->business);
    }

    /** Admin or above can delete accounts. */
    public function delete(User $user, Account $account): bool
    {
        return $user->canManageIn($account->business);
    }
}
