<?php

namespace App\Listeners;

use App\Events\BusinessCreated;
use App\Mail\WelcomeBusiness;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class SendWelcomeEmail implements ShouldQueue
{
    public function handle(BusinessCreated $event): void
    {
        Mail::to($event->owner->email)->send(
            new WelcomeBusiness($event->business, $event->owner),
        );
    }
}
