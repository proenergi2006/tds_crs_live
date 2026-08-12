<?php

namespace App\Support\Approvals;

use App\Models\Penawaran;

class PenawaranPendingApproval implements PendingApprovalProvider
{
    private const ROLE_BM = 8;
    private const ROLE_OM = 10;

    public function label(): string
    {
        return 'penawaran';
    }

    public function stageRoleIds(): array
    {
        return [self::ROLE_BM, self::ROLE_OM];
    }

    public function pendingCountFor(int $roleId): int
    {
        return match ($roleId) {
            self::ROLE_BM => Penawaran::where('disposisi_penawaran', 2)->count(),
            self::ROLE_OM => Penawaran::where('disposisi_penawaran', 3)->count(),
            default => 0,
        };
    }
}
