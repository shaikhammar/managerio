<?php

namespace App\Domain\Translation\Enums;

enum RateCardType: string
{
    case Default = 'default';
    case Client = 'client';
    case Translator = 'translator';

    public function label(): string
    {
        return match ($this) {
            self::Default => 'Default',
            self::Client => 'Client',
            self::Translator => 'Translator',
        };
    }
}
