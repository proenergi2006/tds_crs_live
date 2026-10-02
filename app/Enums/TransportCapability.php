<?php

namespace App\Enums;

enum TransportCapability: string
{
    case Vessel = 'VESSEL';
    case Truck = 'TRUCK';
    case VesselTruck = 'VESSEL_TRUCK';

    public function label(): string
    {
        return match ($this) {
            self::Vessel => 'Kapal',
            self::Truck => 'Truck',
            self::VesselTruck => 'Truck & Kapal',
        };
    }
}
