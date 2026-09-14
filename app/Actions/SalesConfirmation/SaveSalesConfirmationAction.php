<?php

namespace App\Actions\SalesConfirmation;

use App\Enums\SalesConfirmationStatus;
use App\Models\CustomerAdminArnya;
use App\Models\PoCustomer;
use App\Models\SalesConfirmation;
use App\Models\SalesConfirmationApproval;
use Illuminate\Support\Facades\DB;

class SaveSalesConfirmationAction
{
    private const ROLE_ADMIN_FINANCE = '9';

    public function execute(PoCustomer $po, array $data, string $pic): SalesConfirmation
    {
        $idCustomer = $po->id_customer;

        return DB::transaction(function () use ($po, $data, $pic, $idCustomer) {
            $arnya = CustomerAdminArnya::where('id_customer', $idCustomer)->first();
            $limit = (float) ($po->customer?->current_credit_limit ?? 0);

            $adminSummary = $data['admin_summary'] ?? null;

            $sc = SalesConfirmation::updateOrCreate(
                ['po_customer_id' => $po->id_poc],
                [
                    'id_customer'     => $idCustomer,
                    'credit_limit'    => $limit,
                    'not_yet'         => (float) ($arnya->outstanding_current ?? 0),
                    'ov_up_07'        => 0,
                    'ov_under_30'     => (float) ($arnya->overdue_1_30 ?? 0),
                    'ov_under_60'     => (float) ($arnya->overdue_31_60 ?? 0),
                    'ov_under_90'     => (float) ($arnya->overdue_61_90 ?? 0),
                    'ov_up_90'        => (float) ($arnya->overdue_90_plus ?? 0),
                    'disposisi'       => SalesConfirmationStatus::Confirmed,
                    'flag_approval'   => 1,
                    'role_approved'   => self::ROLE_ADMIN_FINANCE,
                    'tgl_approved'    => now(),
                    'lastupdate_by'   => $pic,
                    'lastupdate_time' => now(),
                ]
            );

            if ($sc->wasRecentlyCreated) {
                $sc->created_time = now();
                $sc->created_by   = $pic;
                $sc->save();
            }

            SalesConfirmationApproval::updateOrCreate(
                ['id_sales' => $sc->id],
                [
                    'adm_result'      => 1,
                    'adm_summary'     => $adminSummary ? nl2br($adminSummary) : null,
                    'adm_result_date' => now(),
                    'adm_pic'         => $pic,
                ]
            );

            return $sc->refresh();
        });
    }
}
