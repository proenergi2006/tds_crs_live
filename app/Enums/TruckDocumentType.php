<?php

namespace App\Enums;

enum TruckDocumentType: string
{
    case Stnk = 'STNK';
    case Kir = 'KIR';

    public function label(): string
    {
        return match ($this) {
            self::Stnk => 'STNK',
            self::Kir => 'KIR',
        };
    }
}
