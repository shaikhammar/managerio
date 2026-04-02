<?php

namespace App\Listeners;

use App\Events\QuoteRespondedFromPortal;
use App\Mail\QuotePortalResponseMail;
use App\Services\MailService;
use Illuminate\Support\Facades\Log;

class SendQuotePortalResponseNotification
{
    public function __construct(public MailService $mailService) {}

    public function handle(QuoteRespondedFromPortal $event): void
    {
        $quote = $event->quote;
        $business = $quote->business;

        if (! $business->hasEmailConfigured()) {
            Log::info("Portal response received for quote {$quote->number} but SMTP is not configured.");

            return;
        }

        $decision = $quote->status->value === 'approved' ? 'approved' : 'rejected';

        $this->mailService->mailerFor($business)
            ->to($business->smtp_from_email)
            ->queue(new QuotePortalResponseMail($quote, $business, $decision));
    }
}
