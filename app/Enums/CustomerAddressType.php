<?php

namespace App\Enums;

/**
 * Tipe alamat di `customer_addresses`. Kolom `address_type` disimpan sebagai
 * string biasa, bukan enum di level DB -- jadi file ini yang jadi source of
 * truth buat nilai yang valid. Nambah tipe baru tinggal edit di sini, gak
 * perlu migration.
 */
enum CustomerAddressType: string
{
    case RegisteredNpwp   = 'registered_npwp';
    case HeadOffice       = 'head_office';
    case Billing          = 'billing';
    case Correspondence   = 'correspondence';
    // Alamat lokasi site survei LCR, dipasangkan lewat customer_addresses.id_lcr
    // (FK ke customer_lcr) -- beda dari tipe lain di enum ini yang levelnya customer.
    case SiteAddress      = 'site_address';

    public function label(): string
    {
        return match ($this) {
            self::RegisteredNpwp => 'Alamat Terdaftar NPWP',
            self::HeadOffice     => 'Kantor Pusat',
            self::Billing        => 'Alamat Penagihan',
            self::Correspondence => 'Alamat Korespondensi',
            self::SiteAddress    => 'Alamat Site Survei',
        };
    }
}
