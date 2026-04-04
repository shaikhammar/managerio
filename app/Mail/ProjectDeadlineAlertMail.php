<?php

namespace App\Mail;

use App\Models\Contact;
use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ProjectDeadlineAlertMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Project $project,
        public readonly Contact $translator,
    ) {}

    public function envelope(): Envelope
    {
        $business = $this->project->business;

        return new Envelope(
            from: new Address(
                $business->smtp_from_email ?? config('mail.from.address'),
                $business->smtp_from_name ?? $business->name,
            ),
            to: [new Address($this->translator->email)],
            subject: "Deadline Reminder — {$this->project->name} ({$this->project->reference})",
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.project-deadline-alert');
    }

    /** @return array<Attachment> */
    public function attachments(): array
    {
        return [];
    }
}
