<?php

namespace App\Services;

use App\Models\Business;
use Illuminate\Mail\Mailer;
use Illuminate\Support\Facades\Mail;

class MailService
{
    /**
     * Get a mailer configured with the business's SMTP settings.
     * Falls back to the default application mailer if not configured.
     */
    public function mailerFor(Business $business): Mailer
    {
        if (! $business->hasEmailConfigured()) {
            return Mail::mailer();
        }

        return Mail::build([
            'transport' => 'smtp',
            'host' => $business->smtp_host,
            'port' => $business->smtp_port ?? 587,
            'encryption' => $business->smtp_encryption ?? 'tls',
            'username' => $business->smtp_username,
            'password' => $business->smtp_password,
        ]);
    }
}
