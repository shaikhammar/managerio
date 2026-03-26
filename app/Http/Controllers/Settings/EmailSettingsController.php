<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\EmailSettingsRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmailSettingsController extends Controller
{
    public function edit(Request $request): Response
    {
        $business = $request->user()->currentBusiness();

        return Inertia::render('settings/email', [
            'business' => $business->only([
                'smtp_host',
                'smtp_port',
                'smtp_username',
                'smtp_encryption',
                'smtp_from_name',
                'smtp_from_email',
            ]),
        ]);
    }

    public function update(EmailSettingsRequest $request): RedirectResponse
    {
        $business = $request->user()->currentBusiness();

        $this->authorize('update', $business);

        $data = $request->validated();

        // Only update password if provided
        if (empty($data['smtp_password'])) {
            unset($data['smtp_password']);
        }

        $business->update($data);

        return back()->with('status', 'email-settings-updated');
    }
}
