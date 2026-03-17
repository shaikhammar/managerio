<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Services\Business\BusinessSetupService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BusinessController extends Controller
{
    public function __construct(
        private BusinessSetupService $setupService,
    ) {}

    /**
     * Display business selector / list.
     */
    public function index(Request $request): Response
    {
        $businesses = $request->user()
            ->businesses()
            ->withCount('users')
            ->get()
            ->map(fn ($business) => [
                'id' => $business->id,
                'name' => $business->name,
                'currency_code' => $business->currency_code,
                'country' => $business->country,
                'role' => $business->pivot->role,
                'members_count' => $business->users_count,
            ]);

        return Inertia::render('business/index', [
            'businesses' => $businesses,
        ]);
    }

    /**
     * Show business creation form.
     */
    public function create(): Response
    {
        return Inertia::render('business/create');
    }

    /**
     * Create a new business.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'legal_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'country' => 'required|string|size:2',
            'currency_code' => 'required|string|size:3',
            'fiscal_year_start' => 'nullable|integer|between:1,12',
            'address_line_1' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
        ]);

        $business = $this->setupService->createBusiness($request->user(), $validated);

        // Set as current business
        session(['current_business_id' => $business->id]);

        return redirect()->route('dashboard')
            ->with('success', "Business '{$business->name}' created successfully.");
    }

    /**
     * Switch to a different business.
     */
    public function switch(Request $request, Business $business)
    {
        if (! $business->hasUser($request->user())) {
            abort(403, 'You do not have access to this business.');
        }

        session(['current_business_id' => $business->id]);

        return redirect()->route('dashboard')
            ->with('success', "Switched to '{$business->name}'.");
    }
}
