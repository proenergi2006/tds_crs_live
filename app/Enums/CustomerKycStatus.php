<?php

namespace App\Enums;

/**
 * Tahapan siklus KYC di `customer_verifications`. Kolom `kyc_status`
 * disimpan sebagai string biasa (bukan DB-level enum) -- enum ini source of
 * truth untuk nilai yang valid; menambah tahapan baru cukup edit file ini,
 * tanpa migration baru.
 */
enum CustomerKycStatus: string
{
    case Draft     = 'draft';
    case Forwarded = 'forwarded';
    case Closed    = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Draft     => 'Draft',
            self::Forwarded => 'Diteruskan ke Admin Finance',
            self::Closed    => 'Ditutup',
        };
    }
}
