<?php

namespace App\Actions\PoCustomer;

use App\Enums\PoCustomerPaymentType;
use App\Enums\PoCustomerScProcessState;
use App\Models\CustomerAdminArnya;
use App\Models\PoCustomer;

class ProcessSalesConfirmationGateAction
{
    private const PPN_RATE = 0.11;

    public function execute(PoCustomer $po, string $pic, ?string $ip): array
    {
        $nilaiOrder = round((float) $po->harga_poc * (float) $po->volume_poc * (1 + self::PPN_RATE), 2);

        if ($po->tipe_bayar !== PoCustomerPaymentType::Credit) {
            $creditLimit = null;
            $exposure    = null;
            $headroom    = null;
            $state = PoCustomerScProcessState::Cleared;
        } else {
            $creditLimit = (float) ($po->customer?->current_credit_limit ?? 0);
            $exposure    = (float) (CustomerAdminArnya::where('id_customer', $po->id_customer)->first()?->total_ar ?? 0);
            $headroom    = $creditLimit - $exposure;
            $state = $headroom >= $nilaiOrder
                ? PoCustomerScProcessState::Cleared
                : PoCustomerScProcessState::Blocked;
        }

        $po->update([
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
    }
}
