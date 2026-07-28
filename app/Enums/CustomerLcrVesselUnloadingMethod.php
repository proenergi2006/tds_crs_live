<?php

namespace App\Enums;

/**
 * Metode bongkar muatan kapal di `customer_lcr` (Grup 6: Vessel/Jetty). Kolom
 * `vessel_unloading_method` disimpan sebagai string biasa (bukan DB-level
 * enum) -- enum ini source of truth untuk nilai yang valid.
 */
enum CustomerLcrVesselUnloadingMethod: string
{
    case GrabCrane      = 'grab_crane';
    case ConveyorBelt   = 'conveyor_belt';
    case FloatingCrane  = 'floating_crane';
    case Other          = 'other';

    public function label(): string
    {
        return match ($this) {
            self::GrabCrane     => 'Grab Crane',
            self::ConveyorBelt  => 'Conveyor Belt',
            self::FloatingCrane => 'Floating Crane',
            self::Other         => 'Lainnya',
        };
    }
}
