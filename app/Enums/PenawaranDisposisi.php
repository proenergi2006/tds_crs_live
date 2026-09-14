<?php

namespace App\Enums;

enum PenawaranDisposisi: int
{
    case Unknown = 0;
    case Draft = 1;
    case MenungguVerifikasiBm = 2;
    case MenungguVerifikasiOm = 3;
    case DisetujuiOm = 4;
    case DitolakBm = 5;
    case DitolakOm = 6;

    public function label(): string
    {
        return match ($this) {
            self::Unknown => '-',
            self::Draft => 'Draft',
            self::MenungguVerifikasiBm => 'Menunggu Verifikasi BM',
            self::MenungguVerifikasiOm => 'Menunggu Verifikasi OM',
            self::DisetujuiOm => 'Disetujui OM',
            self::DitolakBm => 'Ditolak BM',
            self::DitolakOm => 'Ditolak OM',
        };
    }
}
