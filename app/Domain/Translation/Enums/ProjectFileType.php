<?php

namespace App\Domain\Translation\Enums;

enum ProjectFileType: string
{
    case SOURCE = 'source';
    case DELIVERABLE = 'deliverable';
    case REFERENCE = 'reference';

    public function label(): string
    {
        return match ($this) {
            self::SOURCE => 'Source',
            self::DELIVERABLE => 'Deliverable',
            self::REFERENCE => 'Reference',
        };
    }
}
