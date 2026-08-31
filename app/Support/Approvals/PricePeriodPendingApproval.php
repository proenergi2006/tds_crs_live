<?php

namespace App\Support\Approvals;

use App\Support\ProductPrice\PricePeriodCompletenessQuery;

class PricePeriodPendingApproval implements PendingApprovalProvider
{
    private const ROLE_CEO = 2;

    public function label(): string
    {
        return 'price_period';
    }

    public function stageRoleIds(): array
    {
        return [self::ROLE_CEO];
    }

    public function pendingCountFor(int $roleId): int
    {
        return match ($roleId) {
            self::ROLE_CEO => app(PricePeriodCompletenessQuery::class)->groupedCurrentAndUpcoming()
                ->filter(fn ($row) => (int) $row->jumlah_belum_lengkap > 0)
                ->count(),
            default => 0,
        };
    }
}
