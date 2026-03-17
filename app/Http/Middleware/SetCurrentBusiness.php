<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetCurrentBusiness
{
    /**
     * Set the current business context from the route parameter or session.
     * This middleware reads the {business} route parameter or falls back to session.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        // Check for business_id in route
        $businessId = $request->route('business')?->id ?? $request->route('business');

        if (! $businessId && $request->has('business_id')) {
            $businessId = $request->input('business_id');
        }

        // Fall back to session or first business
        if (! $businessId) {
            $businessId = session('current_business_id');
        }

        if (! $businessId) {
            $firstBusiness = $user->businesses()->first();
            $businessId = $firstBusiness?->id;
        }

        if ($businessId) {
            session(['current_business_id' => (int) $businessId]);
        }

        return $next($request);
    }
}
