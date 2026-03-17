<?php

namespace App\Domain\Sales\Enums;

enum InvoiceStatus: string
{
    case DRAFT = 'draft';
    case SENT = 'sent';
    case APPROVED = 'approved';
    case PAID = 'paid';
    case PARTIALLY_PAID = 'partially_paid';
    case OVERDUE = 'overdue';
    case VOID = 'void';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::SENT => 'Sent',
            self::APPROVED => 'Approved',
            self::PAID => 'Paid',
            self::PARTIALLY_PAID => 'Partially Paid',
            self::OVERDUE => 'Overdue',
            self::VOID => 'Void',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::SENT => 'blue',
            self::APPROVED => 'indigo',
            self::PAID => 'green',
            self::PARTIALLY_PAID => 'amber',
            self::OVERDUE => 'red',
            self::VOID => 'slate',
            self::CANCELLED => 'rose',
        };
    }
}
