<?php

namespace App\Enums;

/**
 * Status satu step di dalam document_approval_steps (snapshot per step).
 *
 * Kolom `status` disimpan sebagai string biasa (bukan DB-level enum) —
 * lihat CLAUDE.md #6. Enum ini source of truth untuk nilai yang valid.
 */
enum DocumentApprovalStepStatus: string
{
    case Pending  = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending  => 'Menunggu',
            self::Approved => 'Disetujui',
            self::Rejected => 'Ditolak',
        };
    }
}
