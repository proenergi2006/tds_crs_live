<?php

namespace App\Enums;

/**
 * Metode verifikasi kuantitas muatan kapal di `customer_lcr` (Grup 6:
 * Vessel/Jetty). Kolom `vessel_quantity_checking_method` disimpan sebagai
 * string biasa (bukan DB-level enum) -- enum ini source of truth untuk nilai
 * yang valid. `draft_survey` adalah metode standar dry bulk shipping.
 */
enum CustomerLcrVesselQuantityCheckingMethod: string
{
    case DraftSurvey             = 'draft_survey';
    case WeighbridgeAfterUnload  = 'weighbridge_after_unload';
    case LoadmasterCertificate   = 'loadmaster_certificate';
    case Other                   = 'other';

    public function label(): string
    {
        return match ($this) {
            self::DraftSurvey            => 'Draft Survey',
            self::WeighbridgeAfterUnload => 'Weighbridge Setelah Bongkar',
            self::LoadmasterCertificate  => 'Loadmaster Certificate',
            self::Other                  => 'Lainnya',
        };
    }
}
