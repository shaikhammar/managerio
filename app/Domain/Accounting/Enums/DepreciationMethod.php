<?php

namespace App\Domain\Accounting\Enums;

enum DepreciationMethod: string
{
    case StraightLine = 'straight_line';
    case DecliningBalance = 'declining_balance';

    public function label(): string
    {
        return match ($this) {
            self::StraightLine => 'Straight Line',
            self::DecliningBalance => 'Declining Balance',
        };
    }
}
