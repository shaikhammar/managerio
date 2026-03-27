<?php

namespace App\Domain\Translation\Enums;

enum TranslatorSpecialisation: string
{
    case Legal = 'legal';
    case Medical = 'medical';
    case Technical = 'technical';
    case Marketing = 'marketing';
    case Financial = 'financial';
    case IT = 'it';
    case LifeSciences = 'life_sciences';
    case Literary = 'literary';
    case Gaming = 'gaming';
    case General = 'general';

    public function label(): string
    {
        return match ($this) {
            self::Legal => 'Legal',
            self::Medical => 'Medical',
            self::Technical => 'Technical',
            self::Marketing => 'Marketing',
            self::Financial => 'Financial',
            self::IT => 'IT / Software',
            self::LifeSciences => 'Life Sciences',
            self::Literary => 'Literary',
            self::Gaming => 'Gaming / Localisation',
            self::General => 'General',
        };
    }
}
