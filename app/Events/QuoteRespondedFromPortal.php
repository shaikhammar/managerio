<?php

namespace App\Events;

use App\Models\Invoice;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QuoteRespondedFromPortal
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Invoice $quote,
    ) {}
}
