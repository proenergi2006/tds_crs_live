<?php

namespace App\Actions\SalesConfirmation;

use App\Enums\DocumentApprovalStepStatus;
use App\Enums\SalesConfirmationStatus;
use App\Models\SalesConfirmation;
use App\Services\Approval\DocumentApprovalService;
use Illuminate\Support\Facades\DB;

class DecideSalesConfirmationAction
{
    private const ROLE_BM = '8';

    public function execute(SalesConfirmation $sc, DocumentApprovalStepStatus $status, int $actorId, ?string $note, string $pic): SalesConfirmation
    {
        return DB::transaction(function () use ($sc, $status, $actorId, $note, $pic) {
            (new DocumentApprovalService())->decideStep($sc, 1, $status, $actorId, $note);

            $sc->update($status === DocumentApprovalStepStatus::Approved
                ? [
                    'disposisi'       => SalesConfirmationStatus::Confirmed,
                    'role_approved'   => self::ROLE_BM,
                    'tgl_approved'    => now(),
                    'lastupdate_by'   => $pic,
                    'lastupdate_time' => now(),
                ]
                : [
                    'disposisi'       => SalesConfirmationStatus::PendingAdmin,
                    'lastupdate_by'   => $pic,
                    'lastupdate_time' => now(),
                ]);

            return $sc->refresh();
        });
    }
}
