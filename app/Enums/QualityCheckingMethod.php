<?php

namespace App\Enums;

/**
 * Metode verifikasi quality di `customer_logistik.quality_checking_method`.
 * Cuma 2 opsi nyata di form publik saat ini (Lab Test / Lainnya) -- kalau
 * kebutuhan bertambah, cukup tambah case di sini, tanpa migration baru.
 */
enum QualityCheckingMethod: string
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
