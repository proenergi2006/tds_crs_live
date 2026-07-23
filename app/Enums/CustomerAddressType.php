<?php

namespace App\Enums;

/**
 * Tipe alamat di `customer_addresses`. Kolom `address_type` disimpan sebagai
 * string biasa (bukan DB-level enum) -- enum ini source of truth untuk nilai
 * yang valid; menambah tipe baru cukup edit file ini, tanpa migration baru.
 */
enum CustomerAddressType: string
{
    case RegisteredNpwp   = 'registered_npwp';
    case HeadOffice       = 'head_office';
    case Billing          = 'billing';
    case Correspondence   = 'correspondence';

    public function label(): string
    {
        return match ($this) {
            self::RegisteredNpwp => 'Alamat Terdaftar NPWP',
            self::HeadOffice     => 'Kantor Pusat',
            self::Billing        => 'Alamat Penagihan',
            self::Correspondence => 'Alamat Korespondensi',
        };
    }
}
