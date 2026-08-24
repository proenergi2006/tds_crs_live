<?php

namespace App\Enums;

enum CustomerAddressType: string
{
    case RegisteredNpwp   = 'registered_npwp';
    case HeadOffice       = 'head_office';
    case Billing          = 'billing';
    case Correspondence   = 'correspondence';
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
