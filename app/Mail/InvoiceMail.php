<?php

namespace App\Mail;

use App\Models\Business;
use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Invoice $invoice,
        public readonly Business $business,
        public readonly string $recipientName,
    ) {}

    public function envelope(): Envelope
    {
        $subject = match (true) {
            $this->invoice->isQuote() => "Quote {$this->invoice->number} from {$this->business->name}",
            default => "Invoice {$this->invoice->number} from {$this->business->name}",
        };

        $fromEmail = $this->business->smtp_from_email ?? config('mail.from.address');
        $fromName = $this->business->smtp_from_name ?? $this->business->name;

        return new Envelope(
            from: new Address($fromEmail, $fromName),
            subject: $subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.invoice',
        );
    }

    /**
     * @return array<Attachment>
     */
    public function attachments(): array
    {
        $invoice = $this->invoice->load(['lines.account', 'lines.taxCode', 'contact']);
        $business = $this->business;

        $pdfView = $invoice->isQuote() ? 'pdf.quote' : 'pdf.invoice';
        $pdf = Pdf::loadView($pdfView, compact('invoice', 'business'));

        return [
            Attachment::fromData(
                fn () => $pdf->output(),
                "{$invoice->number}.pdf",
            )->withMime('application/pdf'),
        ];
    }
}
