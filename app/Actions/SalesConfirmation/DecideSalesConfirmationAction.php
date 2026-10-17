<?php

namespace App\Actions\SalesConfirmation;

use App\Enums\DocumentApprovalStepStatus;
use App\Enums\SalesConfirmationStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\SalesConfirmation;
use App\Services\Approval\DocumentApprovalService;
use Illuminate\Support\Facades\DB;

class DecideSalesConfirmationAction
{
    public function execute(
        SalesConfirmation $sc,
        DocumentApprovalStepStatus $status,
        int $actorId,
        ?string $note,
        string $pic
    ): SalesConfirmation {
        return DB::transaction(function () use ($sc, $status, $actorId, $note, $pic) {
            $locked   = SalesConfirmation::whereKey($sc->getKey())->lockForUpdate()->firstOrFail();
            $expected = $locked->expectedStepOrder();

            if ($expected === null) {
                throw new BusinessRuleException('Sales Confirmation ini tidak sedang menunggu keputusan.');
            }

            $service = new DocumentApprovalService();
            $cycle   = $service->activeCycle($locked);
            $step    = $cycle?->steps->firstWhere('step_order', $expected);

            if ($cycle === null || (int) $cycle->current_step_order !== $expected || $step === null) {
                throw new BusinessRuleException('Step approval aktif tidak sesuai dengan status Sales Confirmation.');
            }

            $isMissingOmStep = $status === DocumentApprovalStepStatus::Approved
                && $expected === 1
                && $cycle->steps->doesntContain('step_order', 2);

            if ($isMissingOmStep) {
                throw new BusinessRuleException(
                    'Siklus approval Sales Confirmation tidak memiliki step Operation Manager.'
                );
            }

            $service->decideStep($locked, $expected, $status, $actorId, $note);

            $locked->update([
                ...$this->outcome($status, $expected, $step->id_role),
                'lastupdate_by'   => $pic,
                'lastupdate_time' => now(),
            ]);

            return $locked->refresh();
        });
    }

    private function outcome(DocumentApprovalStepStatus $status, int $stepOrder, ?int $roleId): array
    {
        if ($status === DocumentApprovalStepStatus::Rejected) {
            return ['disposisi' => SalesConfirmationStatus::Rejected];
        }

        if ($stepOrder === 1) {
            return ['disposisi' => SalesConfirmationStatus::PendingOm];
        }

        return [
            'disposisi'     => SalesConfirmationStatus::Confirmed,
            'role_approved' => (string) $roleId,
            'tgl_approved'  => now(),
        ];
    }
}
