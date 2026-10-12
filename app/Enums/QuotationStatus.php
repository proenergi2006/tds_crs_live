<?php

namespace App\Enums;

enum QuotationStatus: string
{
    case Draft               = 'draft';
    case WaitingVerification = 'waiting_verification';
    case Approved            = 'approved';
    case Rejected            = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Draft               => 'Draft',
            self::WaitingVerification => 'Menunggu Verifikasi',
            self::Approved            => 'Disetujui',
            self::Rejected            => 'Ditolak',
        };
    }
}
