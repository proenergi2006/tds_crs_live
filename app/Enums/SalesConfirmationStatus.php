<?php

namespace App\Enums;

enum SalesConfirmationStatus: int
{
    case PendingAdmin = 1;
    case PendingBm = 2;
    case Confirmed = 4;

    public function label(): string
    {
        return match ($this) {
            self::PendingAdmin => 'Menunggu Verifikasi Admin',
            self::PendingBm => 'Menunggu Verifikasi BM',
            self::Confirmed => 'Confirmed',
        };
    }
}
