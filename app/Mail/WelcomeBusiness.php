<?php

namespace App\Mail;

use App\Models\Business;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WelcomeBusiness extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Business $business,
        public readonly User $owner,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Welcome to ManagerIO — {$this->business->name} is ready",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.welcome-business',
        );
    }
}
