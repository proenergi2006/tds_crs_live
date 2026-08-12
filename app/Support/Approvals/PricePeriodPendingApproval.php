<?php

namespace App\Support\Approvals;

use App\Support\ProdukHarga\PricePeriodCompletenessQuery;

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

    // definisi "pending" sama kayak DashboardController::pendingCeoPricePeriod() -- jumlah PERIODE, bukan baris produk_hargas
    public function pendingCountFor(int $roleId): int
    {
        return match ($roleId) {
            self::ROLE_CEO => app(PricePeriodCompletenessQuery::class)->grouped()
                ->filter(fn ($row) => (int) $row->jumlah_belum_lengkap > 0)
                ->count(),
            default => 0,
        };
    }
}
