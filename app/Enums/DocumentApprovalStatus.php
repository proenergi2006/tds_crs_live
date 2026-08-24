<?php

namespace App\Enums;

/**
 * Status keseluruhan sebuah document_approvals row (siklus approval multi-step).
 *
 * Kolom `status` di tabel `document_approvals` disimpan sebagai string biasa
 * (bukan DB-level enum) — lihat CLAUDE.md #6. Enum ini yang jadi source of
 * truth untuk nilai yang valid; menambah status baru cukup edit file ini,
 * tanpa migration baru.
 */
enum DocumentApprovalStatus: string
{
    case InProgress = 'in_progress';
    case Approved   = 'approved';
    case Rejected   = 'rejected';
    case Cancelled  = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::InProgress => 'Dalam Proses',
            self::Approved   => 'Disetujui',
            self::Rejected   => 'Ditolak',
            self::Cancelled  => 'Dibatalkan',
        };
    }
}
