<?php

namespace App\Enums;

enum CustomerVerificationStatus: string
{
    case InReview = 'in_review';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::InReview => 'Menunggu Review Admin Finance',
            self::Approved => 'Disetujui',
            self::Rejected => 'Ditolak',
        };
    }
}
