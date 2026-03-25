<?php

namespace App\Domain\Accounting\Enums;

enum AssetStatus: string
{
    case Active = 'active';
    case Retired = 'retired';
    case Disposed = 'disposed';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Retired => 'Retired',
            self::Disposed => 'Disposed',
        };
    }
}
