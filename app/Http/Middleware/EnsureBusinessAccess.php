<?php

namespace App\Http\Middleware;

use App\Models\Business;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureBusinessAccess
{
    /**
     * Ensure the authenticated user has access to the current business.
     * Prevents cross-business data access.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        $businessId = session('current_business_id');

        if (! $businessId) {
            // No business selected — redirect to business selector
            return redirect()->route('business.index');
        }

        $business = Business::find($businessId);

        if (! $business || ! $business->hasUser($user)) {
            // User doesn't belong to this business — clear session and redirect
            session()->forget('current_business_id');

            return redirect()->route('business.index')
                ->with('error', 'You do not have access to that business.');
        }

        // Share current business with all Inertia views
        $role = $business->getUserRole($user);

        inertia()->share('currentBusiness', [
            'id' => $business->id,
            'name' => $business->name,
            'currency_code' => $business->currency_code,
            'role' => $role?->value,
            'can_edit' => $role?->canEdit() ?? false,
            'can_manage' => $role?->canManage() ?? false,
        ]);

        return $next($request);
    }
}
