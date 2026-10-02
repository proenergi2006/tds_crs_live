<?php

namespace App\Enums;

enum TransporterOwnership: string
{
    case Own = 'OWN';
    case Thirdparty = 'THIRDPARTY';

    public function label(): string
    {
        return match ($this) {
            self::Own => 'Milik Sendiri',
            self::Thirdparty => 'Thirdparty',
        };
    }
}
