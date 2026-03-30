<?php

namespace App\Domain\Sales\Enums;

enum InvoiceStatus: string
{
    case DRAFT = 'draft';
    case SENT = 'sent';
    case ACCEPTED = 'accepted';
    case IN_PROGRESS = 'in_progress';
    case DELIVERED = 'delivered';
    case APPROVED = 'approved';
    case PAID = 'paid';
    case PARTIALLY_PAID = 'partially_paid';
    case OVERDUE = 'overdue';
    case VOID = 'void';
    case CANCELLED = 'cancelled';
    case PARTIALLY_RECEIVED = 'partially_received';
    case RECEIVED = 'received';
    case INVOICED = 'invoiced';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::SENT => 'Sent',
            self::ACCEPTED => 'Accepted',
            self::IN_PROGRESS => 'In Progress',
            self::DELIVERED => 'Delivered',
            self::APPROVED => 'Approved',
            self::PAID => 'Paid',
            self::PARTIALLY_PAID => 'Partially Paid',
            self::OVERDUE => 'Overdue',
            self::VOID => 'Void',
            self::CANCELLED => 'Cancelled',
            self::PARTIALLY_RECEIVED => 'Partially Received',
            self::RECEIVED => 'Received',
            self::INVOICED => 'Invoiced',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::SENT => 'blue',
            self::ACCEPTED => 'violet',
            self::IN_PROGRESS => 'cyan',
            self::DELIVERED => 'emerald',
            self::APPROVED => 'indigo',
            self::PAID => 'green',
            self::PARTIALLY_PAID => 'amber',
            self::OVERDUE => 'red',
            self::VOID => 'slate',
            self::CANCELLED => 'rose',
            self::PARTIALLY_RECEIVED => 'purple',
            self::RECEIVED => 'teal',
            self::INVOICED => 'green',
        };
    }
}
