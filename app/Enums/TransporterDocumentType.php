<?php

namespace App\Enums;

enum TransporterDocumentType: string
{
    case Nib = 'NIB';
    case Siup = 'SIUP';
    case Siupal = 'SIUPAL';

    public function label(): string
    {
        return match ($this) {
            self::Nib => 'NIB',
            self::Siup => 'SIUP',
            self::Siupal => 'SIUPAL',
        };
    }
}
