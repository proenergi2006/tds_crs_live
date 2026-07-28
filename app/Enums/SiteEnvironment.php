<?php

namespace App\Enums;

/**
 * Lingkungan sekitar lokasi site (Industri/Pemukiman/Lainnya) -- dipakai
 * `customer_logistik.site_environment` (self-report customer). Konsep sama
 * juga dipakai `customer_lcr.site_environment` (hasil survei), tapi enum ini
 * sengaja generik (bukan di-prefix Customer{Table}) supaya reusable lintas
 * tabel tanpa migration baru.
 */
enum SiteEnvironment: string
{
    case Industrial  = 'industrial';
    case Residential = 'residential';
    case Other       = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Industrial  => 'Industri',
            self::Residential => 'Pemukiman',
            self::Other       => 'Lainnya',
        };
    }
}
