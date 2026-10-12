<?php

namespace App\Enums;

enum QuotationBrand: string
{
    case Tds       = 'tds';
    case Proenergi = 'proenergi';

    public function label(): string
    {
        return match ($this) {
            self::Tds       => 'TDS',
            self::Proenergi => 'Proenergi',
        };
    }
}
