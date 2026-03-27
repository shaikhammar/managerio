<?php

namespace App\Domain\Translation\Enums;

enum TranslatorCertification: string
{
    case ISO17100 = 'iso_17100';
    case ATA = 'ata';
    case NAATI = 'naati';
    case ITI = 'iti';
    case CIoL = 'ciol';
    case FIT = 'fit';
    case DipTrans = 'dip_trans';

    public function label(): string
    {
        return match ($this) {
            self::ISO17100 => 'ISO 17100',
            self::ATA => 'ATA Certified',
            self::NAATI => 'NAATI Certified',
            self::ITI => 'ITI Member',
            self::CIoL => 'CIoL Member',
            self::FIT => 'FIT Member',
            self::DipTrans => 'Dip Trans (IoLET)',
        };
    }
}
