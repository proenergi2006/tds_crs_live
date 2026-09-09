<?php

namespace App\Actions\SalesConfirmation;

use App\Enums\SalesConfirmationStatus;
use App\Models\CustomerAdminArnya;
use App\Models\PoCustomer;
use App\Models\SalesColleteral;
use App\Models\SalesConfirmation;
use App\Models\SalesConfirmationApproval;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class SaveSalesConfirmationAction
{
    public function execute(PoCustomer $po, array $data, ?UploadedFile $lampiran, string $pic): SalesConfirmation
    {
        $idCustomer = $po->id_customer;

        return DB::transaction(function () use ($po, $data, $lampiran, $pic, $idCustomer) {
            CustomerAdminArnya::updateOrCreate(
                ['id_customer' => $idCustomer],
                [
                    'not_yet'     => $data['not_yet'] ?? 0,
                    'ov_up_07'    => $data['ov_up_07'] ?? 0,
                    'ov_under_30' => $data['ov_under_30'] ?? 0,
                    'ov_under_60' => $data['ov_under_60'] ?? 0,
                    'ov_under_90' => $data['ov_under_90'] ?? 0,
                    'ov_up_90'    => $data['ov_up_90'] ?? 0,
                ]
            );

            $typeCustomer  = $data['type_customer'] ?? null;
            $adminSummary  = $data['admin_summary'] ?? null;

            $sc = SalesConfirmation::updateOrCreate(
                ['po_customer_id' => $po->id_poc],
                [
                    'id_customer'     => $idCustomer,
                    'credit_limit'    => $data['credit_limit'] ?? 0,
                    'not_yet'         => $data['not_yet'] ?? 0,
                    'ov_up_07'        => $data['ov_up_07'] ?? 0,
                    'ov_under_30'     => $data['ov_under_30'] ?? 0,
                    'ov_under_60'     => $data['ov_under_60'] ?? 0,
                    'ov_under_90'     => $data['ov_under_90'] ?? 0,
                    'ov_up_90'        => $data['ov_up_90'] ?? 0,
                    'reminding'       => $data['reminding'] ?? null,
                    'po_status'       => $data['po_status'] ?? null,
                    'po_volume'       => $data['po_volume'] ?? 0,
                    'po_amount'       => $data['po_amount'] ?? 0,
                    'proposed_status' => (int) ($data['proposed_status'] ?? 0),
                    'add_top'         => (int) ($data['add_top'] ?? 0),
                    'add_cl'          => (int) ($data['add_cl'] ?? 0),
                    'disposisi'       => SalesConfirmationStatus::PendingBm,
                    'flag_approval'   => 0,
                    'role_approved'   => null,
                    'tgl_approved'    => null,
                    'type_customer'   => $typeCustomer,
                    'customer_amount' => $typeCustomer == 1 ? ($data['customer_amount'] ?? 0) : 0,
                    'customer_date'   => $typeCustomer == 1 ? ($data['customer_date'] ?? null) : null,
                    'lastupdate_by'   => $pic,
                    'lastupdate_time' => now(),
                ]
            );

            if ($sc->wasRecentlyCreated) {
                $sc->created_time = now();
                $sc->created_by   = $pic;
                $sc->save();
            }

            if ($lampiran) {
                $filename = $sc->id.'_'.md5($lampiran->getClientOriginalName()).'.'.$lampiran->getClientOriginalExtension();
                $path = $lampiran->storeAs('lampiran_unblock', $filename, 'public');

                $sc->lampiran_unblock     = $path;
                $sc->lampiran_unblock_ori = $lampiran->getClientOriginalName();
                $sc->save();
            }

            if ($typeCustomer == 2) {
                SalesColleteral::where('sales_id', $sc->id)->delete();
                $dates = $data['customer_date_coll'] ?? [];
                $amts  = $data['customer_amount_coll'] ?? [];
                $items = $data['item_coll'] ?? [];
                foreach ($dates as $i => $d) {
                    if (!$d) {
                        continue;
                    }
                    SalesColleteral::create([
                        'sales_id' => $sc->id,
                        'date'     => $d,
                        'amount'   => (float) ($amts[$i] ?? 0),
                        'item'     => (string) ($items[$i] ?? ''),
                    ]);
                }
            } else {
                SalesColleteral::where('sales_id', $sc->id)->delete();
            }

            SalesConfirmationApproval::updateOrCreate(
                ['id_sales' => $sc->id],
                [
                    'adm_result'      => (int) ($data['approval'] ?? 0),
                    'adm_summary'     => $adminSummary ? nl2br($adminSummary) : null,
                    'adm_result_date' => now(),
                    'adm_pic'         => $pic,
                    'bm_result' => 0, 'bm_summary' => null, 'bm_result_date' => null, 'bm_pic' => null,
                    'om_result' => 0, 'om_summary' => null, 'om_result_date' => null, 'om_pic' => null,
                    'mgr_result'=> 0, 'mgr_summary'=> null, 'mgr_result_date'=> null, 'mgr_pic'=> null,
                    'cfo_result'=> 0, 'cfo_summary'=> null, 'cfo_result_date'=> null, 'cfo_pic'=> null,
                ]
            );

            return $sc->refresh();
        });
    }
}
