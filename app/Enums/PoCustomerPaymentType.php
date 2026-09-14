<?php

namespace App\Enums;

enum PoCustomerPaymentType: string
{
    case Cbd = 'CBD';
    case Cod = 'COD';
    case Credit = 'CREDIT';

    public function label(): string
    {
        return match ($this) {
            self::Cbd => 'CBD',
            self::Cod => 'COD',
            self::Credit => 'Credit',
        };
    }
}
