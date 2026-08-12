<?php

namespace App\Support\Approvals;

interface PendingApprovalProvider
{
    public function label(): string;

    public function stageRoleIds(): array;

    public function pendingCountFor(int $roleId): int;
}
