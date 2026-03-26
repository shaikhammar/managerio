<?php

namespace App\Domain\Translation\Enums;

enum ProjectAssignmentRole: string
{
    case TRANSLATOR = 'translator';
    case EDITOR = 'editor';
    case PROOFREADER = 'proofreader';
    case DTP = 'dtp';

    public function label(): string
    {
        return match ($this) {
            self::TRANSLATOR => 'Translator',
            self::EDITOR => 'Editor',
            self::PROOFREADER => 'Proofreader',
            self::DTP => 'DTP Operator',
        };
    }
}
