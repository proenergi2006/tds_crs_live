<?php

namespace App\Enums;

enum SalesConfirmationStatus: int
{
    case PendingAdmin = 1;
    case PendingBm = 2;
    case PendingOm = 3;
    case Confirmed = 4;
    case Rejected = 5;

    public function label(): string
    {
        return match ($this) {
            self::PendingAdmin => 'Menunggu Verifikasi Admin',
            self::PendingBm => 'Menunggu Verifikasi BM',
            self::PendingOm => 'Menunggu Verifikasi OM',
            self::Confirmed => 'Confirmed',
            self::Rejected => 'Ditolak',
        };
    }
}
