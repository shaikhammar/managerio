<?php

namespace App\Domain\Sales\Enums;

enum InvoiceType: string
{
    case QUOTE = 'quote';
    case INVOICE = 'invoice';
    case CREDIT_NOTE = 'credit_note';
    case PURCHASE_INVOICE = 'purchase_invoice';

    public function label(): string
    {
        return match ($this) {
            self::QUOTE => 'Quote',
            self::INVOICE => 'Invoice',
            self::CREDIT_NOTE => 'Credit Note',
            self::PURCHASE_INVOICE => 'Purchase Invoice',
        };
    }

    public function prefix(): string
    {
        return match ($this) {
            self::QUOTE => 'QT',
            self::INVOICE => 'INV',
            self::CREDIT_NOTE => 'CN',
            self::PURCHASE_INVOICE => 'PI',
        };
    }
}
