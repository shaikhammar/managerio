<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    /** Any business member can browse payments. */
    public function viewAny(User $user): bool
    {
        $business = $user->currentBusiness();

        return $business !== null && $user->belongsToBusiness($business);
    }

    /** Any business member can view a payment. */
    public function view(User $user, Payment $payment): bool
    {
        return $user->belongsToBusiness($payment->business);
    }

    /** Editor or above can record payments. */
    public function create(User $user): bool
    {
        $business = $user->currentBusiness();

        return $business !== null && $user->canEditIn($business);
    }

    /** Admin or above can delete payments. */
    public function delete(User $user, Payment $payment): bool
    {
        return $user->canManageIn($payment->business);
    }
}
