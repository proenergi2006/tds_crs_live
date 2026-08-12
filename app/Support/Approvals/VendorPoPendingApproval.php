<?php

namespace App\Support\Approvals;

use App\Enums\VendorPoApprovalState;
use App\Models\VendorPo;

class VendorPoPendingApproval implements PendingApprovalProvider
{
    private const ROLE_CEO = 2;

    public function label(): string
    {
        return 'vendor_po';
    }

    public function stageRoleIds(): array
    {
        return [self::ROLE_CEO];
    }

    // CFO sengaja gak masuk sini -- disposisi_po=0 gak bisa dibedain draft vs nunggu CFO beneran, jangan tambahin sampe itu dibenahi
    public function pendingCountFor(int $roleId): int
    {
        return match ($roleId) {
            self::ROLE_CEO => VendorPo::where('disposisi_po', VendorPoApprovalState::WaitingCeo->value)->count(),
            default => 0,
        };
    }
}
