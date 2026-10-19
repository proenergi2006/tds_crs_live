<?php

namespace App\Enums;

enum SalesConfirmationStatus: int
{
    case PendingAdmin = 1;
    case InApproval = 2;
    case Confirmed = 4;
    case Rejected = 5;

    public function label(): string
    {
        return match ($this) {
            self::PendingAdmin => 'Menunggu Verifikasi Admin',
            self::InApproval => 'Menunggu Verifikasi',
            self::Confirmed => 'Confirmed',
            self::Rejected => 'Ditolak',
        };
    }
}
