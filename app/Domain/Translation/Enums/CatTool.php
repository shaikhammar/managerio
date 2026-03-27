<?php

namespace App\Domain\Translation\Enums;

enum CatTool: string
{
    case Manual = 'manual';
    case Trados = 'trados';
    case MemoQ = 'memoq';
    case Phrase = 'phrase';
    case Wordfast = 'wordfast';
    case DejaVu = 'deja_vu';
    case XTM = 'xtm';
    case Crowdin = 'crowdin';
    case Smartcat = 'smartcat';
    case Transifex = 'transifex';

    public function label(): string
    {
        return match ($this) {
            self::Manual => 'Manual Entry',
            self::Trados => 'SDL Trados',
            self::MemoQ => 'memoQ',
            self::Phrase => 'Phrase (Memsource)',
            self::Wordfast => 'Wordfast',
            self::DejaVu => 'Déjà Vu',
            self::XTM => 'XTM Cloud',
            self::Crowdin => 'Crowdin',
            self::Smartcat => 'Smartcat',
            self::Transifex => 'Transifex',
        };
    }
}
