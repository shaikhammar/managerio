<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\BusinessRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BusinessSettingsController extends Controller
{
    public function edit(Request $request): Response
    {
        return Inertia::render('settings/business', [
            'business' => $request->user()->currentBusiness(),
        ]);
    }

    public function update(BusinessRequest $request): RedirectResponse
    {
        $business = $request->user()->currentBusiness();

        $this->authorize('update', $business);

        $business->update($request->validated());

        return back()->with('status', 'business-updated');
    }
}
