<?php

namespace App\Domain\Translation\Enums;

enum BillingUnit: string
{
    case Word = 'word';
    case Hour = 'hour';
    case Page = 'page';
    case Minute = 'minute';
    case Line = 'line';
    case Character = 'character';

    public function label(): string
    {
        return match ($this) {
            self::Word => 'Word',
            self::Hour => 'Hour',
            self::Page => 'Page',
            self::Minute => 'Minute',
            self::Line => 'Line',
            self::Character => 'Character',
        };
    }
}
