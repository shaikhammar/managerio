<?php

namespace App\Domain\Payments\Enums;

enum PaymentType: string
{
    case RECEIPT = 'receipt';        // Money received from customer
    case PAYMENT = 'payment';       // Money paid to supplier

    public function label(): string
    {
        return match ($this) {
            self::RECEIPT => 'Receipt',
            self::PAYMENT => 'Payment',
        };
    }

    public function prefix(): string
    {
        return match ($this) {
            self::RECEIPT => 'REC',
            self::PAYMENT => 'PAY',
        };
    }
}
