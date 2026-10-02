<?php

namespace App\Enums;

enum TransportType: string
{
    case Vessel = 'VESSEL';
    case Truck = 'TRUCK';

    public function label(): string
    {
        return match ($this) {
            self::Vessel => 'Kapal',
            self::Truck => 'Truck',
        };
    }
}
