<?php

namespace App\Domain\Translation\Enums;

enum CatTool: string
{
    case Manual = 'manual';
    case Trados = 'trados';
    case MemoQ = 'memoq';
    case Phrase = 'phrase';

    public function label(): string
    {
        return match ($this) {
            self::Manual => 'Manual Entry',
            self::Trados => 'SDL Trados',
            self::MemoQ => 'memoQ',
            self::Phrase => 'Phrase (Memsource)',
        };
    }
}
