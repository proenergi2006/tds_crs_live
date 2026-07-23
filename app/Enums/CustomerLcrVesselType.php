<?php

namespace App\Enums;

/**
 * Jenis kapal pengangkut di `customer_lcr` (Grup 6: Vessel/Jetty). Kolom
 * `vessel_type` disimpan sebagai string biasa (bukan DB-level enum) -- enum
 * ini source of truth untuk nilai yang valid, menambah jenis baru cukup edit
 * file ini, tanpa migration baru.
 */
enum CustomerLcrVesselType: string
{
    case BulkCarrier         = 'bulk_carrier';
    case Barge               = 'barge';
    case SelfPropelledBarge  = 'self_propelled_barge';
    case Other               = 'other';

    public function label(): string
    {
        return match ($this) {
            self::BulkCarrier        => 'Bulk Carrier',
            self::Barge              => 'Tongkang (Barge)',
            self::SelfPropelledBarge => 'Self-Propelled Barge (SPOB)',
            self::Other              => 'Lainnya',
        };
    }
}
