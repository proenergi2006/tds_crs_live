<?php

namespace App\Enums;

use App\Models\VendorPo;

enum VendorPoApprovalState: int
{
    case Draft      = 0;
    case DitolakCfo = 1; // synthetic sentinel; nilai DB sebenarnya 0 — lihat resolve()
    case WaitingCeo = 2;
    case DitolakCeo = 3; // synthetic sentinel; nilai DB sebenarnya 0 — lihat resolve()
    case Approved   = 4;

    public function label(): string
    {
        return match ($this) {
            self::Draft      => 'Draft',
            self::DitolakCfo => 'Ditolak CFO',
            self::WaitingCeo => 'Menunggu Verifikasi CEO',
            self::DitolakCeo => 'Ditolak CEO',
            self::Approved   => 'Disetujui',
        };
    }

    public static function resolve(VendorPo $po): self
    {
        $d = (int) $po->disposisi_po;

        if ($d === 4) return self::Approved;
        if ($d === 2) return self::WaitingCeo;

        // d === 0 — disambiguasi via result columns.
        // Reliable karena edit() menjamin kolom ini null saat disposisi_po direset ke 0.
        if ((int) $po->ceo_result === 2) return self::DitolakCeo;
        if ((int) $po->cfo_result === 2) return self::DitolakCfo;

        return self::Draft;
    }
}
