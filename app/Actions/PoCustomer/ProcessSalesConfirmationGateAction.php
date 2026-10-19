<?php

namespace App\Actions\PoCustomer;

use App\Enums\PoCustomerPaymentType;
use App\Enums\PoCustomerScProcessState;
use App\Exceptions\BusinessRuleException;
use App\Models\CustomerAdminArnya;
use App\Models\PoCustomer;
use Illuminate\Support\Facades\DB;

class ProcessSalesConfirmationGateAction
{
    public function execute(PoCustomer $po, string $pic, ?string $ip): array
    {
        return DB::transaction(function () use ($po, $pic, $ip) {
            $locked = PoCustomer::whereKey($po->getKey())->lockForUpdate()->firstOrFail();

            $this->assertProcessable($locked);

            $locked->load(['customer.latestApprovedVerification', 'items']);

            $nilaiOrder = $locked->orderValueWithPpn();

            if ($locked->tipe_bayar !== PoCustomerPaymentType::Credit) {
                $creditLimit = null;
                $exposure    = null;
                $headroom    = null;
                $state       = PoCustomerScProcessState::Cleared;
            } else {
                $creditLimit = (float) ($locked->customer?->current_credit_limit ?? 0);
                $arnya       = CustomerAdminArnya::where('id_customer', $locked->id_customer)->first();
                $exposure    = (float) ($arnya?->total_ar ?? 0);
                $headroom    = $creditLimit - $exposure;
                $state       = $headroom >= $nilaiOrder
                    ? PoCustomerScProcessState::Cleared
                    : PoCustomerScProcessState::Blocked;
            }

            $locked->update([
                'sc_process_state' => $state,
                'lastupdate_time'  => now(),
                'lastupdate_ip'    => $ip,
                'lastupdate_by'    => $pic,
            ]);

            return [
                'sc_process_state'     => $state,
                'nilai_order'          => $nilaiOrder,
                'current_credit_limit' => $creditLimit,
                'exposure'             => $exposure,
                'headroom'             => $headroom,
            ];
        });
    }

    private function assertProcessable(PoCustomer $po): void
    {
        if ($po->sc_process_state === PoCustomerScProcessState::Cleared) {
            throw new BusinessRuleException('PO Customer sudah lolos gerbang Sales Confirmation.');
        }

        if ($po->sc_process_state === PoCustomerScProcessState::Blocked && $po->activeUnblockRequest() !== null) {
            throw new BusinessRuleException('Ada pengajuan Unblock yang masih diproses. Selesaikan dulu.');
        }
    }
}
