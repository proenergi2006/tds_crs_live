<?php

namespace App\Actions\SalesConfirmation;

use App\Enums\SalesConfirmationStatus;
use App\Models\SalesConfirmation;
use App\Models\SalesConfirmationApproval;
use Illuminate\Support\Facades\DB;

class DecideSalesConfirmationAction
{
    private const ROLE_BM = '8';

    public function execute(SalesConfirmation $sc, int $result, ?string $summary, string $pic): SalesConfirmation
    {
        return DB::transaction(function () use ($sc, $result, $summary, $pic) {
            SalesConfirmationApproval::updateOrCreate(
                ['id_sales' => $sc->id],
                [
                    'bm_result'      => $result,
                    'bm_summary'     => $summary ? nl2br($summary) : null,
                    'bm_result_date' => now(),
                    'bm_pic'         => $pic,
                ]
            );

            $sc->flag_approval = $result;
            $sc->role_approved = self::ROLE_BM;
            $sc->tgl_approved = now();
            $sc->disposisi = $result === 1 ? SalesConfirmationStatus::Confirmed : SalesConfirmationStatus::PendingAdmin;
            $sc->lastupdate_by = $pic;
            $sc->lastupdate_time = now();
            $sc->save();

            return $sc->refresh();
        });
    }
}
