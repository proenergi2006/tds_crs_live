<?php

namespace App\Enums;

/**
 * Jam operasional site di `customer_logistik.operating_hours`. Scoped ke
 * tabel ini (bukan generik) karena `customer_lcr.operating_hours` menyimpan
 * struktur berbeda (json array hasil survei), bukan single-select kategori
 * yang sama.
 */
enum CustomerLogistikOperatingHours: string
{
    case StandardOfficeHours = 'standard_office_hours';
    case TwentyFourHours     = 'twenty_four_hours';
    case Other               = 'other';

    public function label(): string
    {
        return match ($this) {
            self::StandardOfficeHours => '08.00 - 17.00',
            self::TwentyFourHours     => '24 Hours',
            self::Other               => 'Lainnya',
        };
    }
}
