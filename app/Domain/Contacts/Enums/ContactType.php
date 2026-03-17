<?php

namespace App\Domain\Contacts\Enums;

enum ContactType: string
{
    case CUSTOMER = 'customer';
    case SUPPLIER = 'supplier';
    case BOTH = 'both';

    public function label(): string
    {
        return match ($this) {
            self::CUSTOMER => 'Customer',
            self::SUPPLIER => 'Supplier',
            self::BOTH => 'Customer & Supplier',
        };
    }
}
