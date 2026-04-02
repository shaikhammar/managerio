<?php

namespace App\Mail;

use App\Models\Business;
use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class QuotePortalResponseMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Invoice $quote,
        public readonly Business $business,
        public readonly string $decision,
    ) {}

    public function envelope(): Envelope
    {
        $clientName = $this->quote->contact?->name ?? 'Client';
        $decision = $this->decision === 'approved' ? 'Approved' : 'Rejected';

        return new Envelope(
            from: new Address(
                $this->business->smtp_from_email ?? config('mail.from.address'),
                $this->business->smtp_from_name ?? $this->business->name,
            ),
            to: [new Address($this->business->smtp_from_email)],
            subject: "Quote {$this->quote->number} {$decision} by {$clientName}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.quote-portal-response',
        );
    }

    /** @return array<Attachment> */
    public function attachments(): array
    {
        return [];
    }
}
