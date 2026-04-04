<?php

namespace App\Console\Commands;

use App\Domain\Sales\Enums\InvoiceStatus;
use App\Domain\Sales\Enums\InvoiceType;
use App\Mail\InvoiceReminderMail;
use App\Models\Invoice;
use App\Services\MailService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendInvoiceReminders extends Command
{
    protected $signature = 'invoice-reminders:send';

    protected $description = 'Send payment reminder emails for overdue SENT invoices';

    public function handle(MailService $mailService): int
    {
        $today = CarbonImmutable::today();
        $cutoff = $today->subDays(7);
        $count = 0;

        Invoice::withoutGlobalScopes()
            ->where('type', InvoiceType::INVOICE)
            ->where('status', InvoiceStatus::SENT)
            ->whereNotNull('due_date')
            ->where('due_date', '<', $today)
            ->where(fn ($q) => $q
                ->whereNull('last_reminder_sent_at')
                ->orWhere('last_reminder_sent_at', '<=', $cutoff)
            )
            ->with(['contact', 'business'])
            ->each(function (Invoice $invoice) use ($mailService, &$count): void {
                $business = $invoice->business;
                $contactEmail = $invoice->contact?->email;

                if (! $contactEmail) {
                    return;
                }

                if (! $business->hasEmailConfigured()) {
                    Log::info("Skipping invoice reminder for {$invoice->number}: SMTP not configured.");

                    return;
                }

                $mailService->mailerFor($business)
                    ->to($contactEmail)
                    ->queue(new InvoiceReminderMail($invoice));

                $invoice->update(['last_reminder_sent_at' => now()]);
                $count++;
            });

        $this->info("Sent {$count} invoice ".($count === 1 ? 'reminder' : 'reminders').'.');

        return self::SUCCESS;
    }
}
