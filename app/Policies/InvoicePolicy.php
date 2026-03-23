<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    /** Any business member can browse invoices/quotes/credit notes. */
    public function viewAny(User $user): bool
    {
        $business = $user->currentBusiness();

        return $business !== null && $user->belongsToBusiness($business);
    }

    /** Any business member can view an invoice. */
    public function view(User $user, Invoice $invoice): bool
    {
        return $user->belongsToBusiness($invoice->business);
    }

    /** Editor or above can create invoices/quotes/credit notes. */
    public function create(User $user): bool
    {
        $business = $user->currentBusiness();

        return $business !== null && $user->canEditIn($business);
    }

    /** Editor or above can update draft invoices. */
    public function update(User $user, Invoice $invoice): bool
    {
        return $user->canEditIn($invoice->business);
    }

    /** Admin or above can void an invoice. */
    public function void(User $user, Invoice $invoice): bool
    {
        return $user->canManageIn($invoice->business);
    }

    /** Admin or above can delete invoices. */
    public function delete(User $user, Invoice $invoice): bool
    {
        return $user->canManageIn($invoice->business);
    }
}
