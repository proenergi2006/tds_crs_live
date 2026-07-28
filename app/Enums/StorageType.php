<?php

namespace App\Enums;

/**
 * Jenis penyimpanan (Indoor/Outdoor/Lainnya) -- dipakai
 * `customer_logistik.storage_type`. Enum generik (bukan di-prefix
 * Customer{Table}) supaya reusable lintas tabel sejenis (mis. `customer_lcr`)
 * tanpa migration baru.
 */
enum StorageType: string
{
    case Indoor  = 'indoor';
    case Outdoor = 'outdoor';
    case Other   = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Indoor  => 'Indoor',
            self::Outdoor => 'Outdoor',
            self::Other   => 'Lainnya',
        };
    }
}
