<?php

namespace App\Domain\Translation\Enums;

enum TranslatorAvailability: string
{
    case Available = 'available';
    case Busy = 'busy';
    case OnLeave = 'on_leave';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Available',
            self::Busy => 'Busy',
            self::OnLeave => 'On Leave',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Available => 'green',
            self::Busy => 'yellow',
            self::OnLeave => 'gray',
        };
    }
}
