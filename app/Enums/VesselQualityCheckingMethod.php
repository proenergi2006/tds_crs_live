<?php

namespace App\Enums;

// Metode verifikasi quality buat pengiriman via vessel/jetty. Dipisah dari
// QualityCheckingMethod (truk) karena kolomnya beda sendiri
// (customer_lcr.vessel_quality_checking_method), walau daftar case-nya
// kebetulan sama persis sekarang.
enum VesselQualityCheckingMethod: string
{
    case LabTest = 'lab_test';
    case Other   = 'other';

    public function label(): string
    {
        return match ($this) {
            self::LabTest => 'Lab Test',
            self::Other   => 'Lainnya',
        };
    }
}
