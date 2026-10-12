<?php

namespace App\Support\Approvals;

use App\Enums\QuotationVerifierRole;
use App\Models\Quotation;

class QuotationPendingApproval implements PendingApprovalProvider
{
    public function label(): string
    {
        return 'penawaran';
    }

    public function stageRoleIds(): array
    {
        return array_map(fn (QuotationVerifierRole $role) => $role->value, QuotationVerifierRole::cases());
    }

    public function pendingCountFor(int $roleId): int
    {
        return Quotation::waitingAtRoles([$roleId])->count();
    }
}
