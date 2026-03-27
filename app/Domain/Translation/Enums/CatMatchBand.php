<?php

namespace App\Domain\Translation\Enums;

enum CatMatchBand: string
{
    case ContextMatch = 'context_match';
    case ExactMatch = 'exact_match';
    case Fuzzy95_99 = 'fuzzy_95_99';
    case Fuzzy85_94 = 'fuzzy_85_94';
    case Fuzzy75_84 = 'fuzzy_75_84';
    case Fuzzy50_74 = 'fuzzy_50_74';
    case NoMatch = 'no_match';
    case Repetitions = 'repetitions';

    public function label(): string
    {
        return match ($this) {
            self::ContextMatch => 'Context Match (101%+)',
            self::ExactMatch => 'Exact Match (100%)',
            self::Fuzzy95_99 => 'Fuzzy 95–99%',
            self::Fuzzy85_94 => 'Fuzzy 85–94%',
            self::Fuzzy75_84 => 'Fuzzy 75–84%',
            self::Fuzzy50_74 => 'Fuzzy 50–74%',
            self::NoMatch => 'No Match (0–49%)',
            self::Repetitions => 'Repetitions',
        };
    }

    /** Default discount percentage applied to this band (industry standard). */
    public function defaultDiscountPercent(): int
    {
        return match ($this) {
            self::ContextMatch => 100,
            self::ExactMatch => 70,
            self::Fuzzy95_99 => 60,
            self::Fuzzy85_94 => 40,
            self::Fuzzy75_84 => 25,
            self::Fuzzy50_74 => 15,
            self::NoMatch => 0,
            self::Repetitions => 75,
        };
    }
}
