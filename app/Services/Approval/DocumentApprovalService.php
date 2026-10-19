<?php

namespace App\Services\Approval;

use App\Enums\DocumentApprovalStatus;
use App\Enums\DocumentApprovalStepStatus;
use App\Models\ApprovalTemplate;
use App\Models\DocumentApproval;
use App\Models\DocumentApprovalStep;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class DocumentApprovalService
{
    public function activeTemplate(string $templateCode): ?ApprovalTemplate
    {
        return ApprovalTemplate::with('steps')
            ->where('code', $templateCode)
            ->where('is_active', true)
            ->first();
    }

    public function stepOrderForRole(DocumentApproval $cycle, int $roleId): ?int
    {
        return $cycle->steps->firstWhere('id_role', $roleId)?->step_order;
    }

    public function startSingleStepCycle(Model $approvable, int $roleId): DocumentApproval
    {
        $approval = $approvable->documentApprovals()->create([
            'id_template'        => null,
            'status'             => DocumentApprovalStatus::InProgress,
            'current_step_order' => 1,
            'started_at'         => now(),
        ]);

        DocumentApprovalStep::create([
            'id_approval'      => $approval->id_approval,
            'id_template_step' => null,
            'id_role'          => $roleId,
            'step_order'       => 1,
            'status'           => DocumentApprovalStepStatus::Pending,
        ]);

        return $approval;
    }

    public function startCycleForRoles(Model $approvable, array $roleIds): DocumentApproval
    {
        if ($roleIds === []) {
            throw new InvalidArgumentException('Daftar role approval tidak boleh kosong.');
        }

        $approval = $approvable->documentApprovals()->create([
            'id_template'        => null,
            'status'             => DocumentApprovalStatus::InProgress,
            'current_step_order' => 1,
            'started_at'         => now(),
        ]);

        foreach (array_values($roleIds) as $index => $roleId) {
            DocumentApprovalStep::create([
                'id_approval'      => $approval->id_approval,
                'id_template_step' => null,
                'id_role'          => (int) $roleId,
                'step_order'       => $index + 1,
                'status'           => DocumentApprovalStepStatus::Pending,
            ]);
        }

        return $approval;
    }

    public function startCycle(Model $approvable, string $templateCode, ?array $stepOrders = null): DocumentApproval
    {
        $template = $this->activeTemplate($templateCode);

        if (!$template || $template->steps->isEmpty()) {
            Log::error("Approval template {$templateCode} tidak ditemukan/tidak lengkap saat memulai siklus.", [
                'approvable_type' => $approvable::class,
                'approvable_id'   => $approvable->getKey(),
            ]);

            throw new \RuntimeException("Approval template {$templateCode} belum ter-setup dengan benar.");
        }

        $steps = $stepOrders === null
            ? $template->steps
            : $template->steps->whereIn('step_order', $stepOrders);

        if ($steps->isEmpty()) {
            throw new \RuntimeException(
                "stepOrders yang diberikan tidak cocok dengan step manapun di template {$templateCode}."
            );
        }

        $approval = $approvable->documentApprovals()->create([
            'id_template'        => $template->id_template,
            'status'             => DocumentApprovalStatus::InProgress,
            'current_step_order' => $steps->min('step_order'),
            'started_at'         => now(),
        ]);

        foreach ($steps as $step) {
            DocumentApprovalStep::create([
                'id_approval'      => $approval->id_approval,
                'id_template_step' => $step->id_step,
                'id_role'          => $step->id_role,
                'step_order'       => $step->step_order,
                'status'           => DocumentApprovalStepStatus::Pending,
            ]);
        }

        return $approval;
    }

    public function activeCycle(Model $approvable): ?DocumentApproval
    {
        return $approvable->documentApprovals()
            ->with('steps')
            ->where('status', DocumentApprovalStatus::InProgress)
            ->latest('id_approval')
            ->first();
    }

    public function cancelCycle(DocumentApproval $cycle, int $actorId, ?int $roleId, string $note): DocumentApproval
    {
        $stepOrder = ((int) $cycle->steps()->max('step_order')) + 1;

        $this->appendCancellationStep($cycle, $stepOrder, $actorId, $roleId, $note);

        $cycle->update([
            'status'             => DocumentApprovalStatus::Cancelled,
            'current_step_order' => null,
            'completed_at'       => now(),
        ]);

        return $cycle->refresh();
    }

    public function startCancelledCycle(Model $approvable, int $actorId, ?int $roleId, string $note): DocumentApproval
    {
        $cycle = $approvable->documentApprovals()->create([
            'id_template'        => null,
            'status'             => DocumentApprovalStatus::Cancelled,
            'current_step_order' => null,
            'started_at'         => now(),
            'completed_at'       => now(),
        ]);

        $this->appendCancellationStep($cycle, 1, $actorId, $roleId, $note);

        return $cycle->refresh();
    }

    private function appendCancellationStep(
        DocumentApproval $cycle,
        int $stepOrder,
        int $actorId,
        ?int $roleId,
        string $note
    ): void
    {
        DocumentApprovalStep::create([
            'id_approval'      => $cycle->id_approval,
            'id_template_step' => null,
            'id_role'          => $roleId,
            'step_order'       => $stepOrder,
            'status'           => DocumentApprovalStepStatus::Cancelled,
            'actor_id'         => $actorId,
            'acted_at'         => now(),
            'decision_note'    => $note,
        ]);
    }

    public function decideStep(
        Model $approvable,
        int $stepOrder,
        DocumentApprovalStepStatus $status,
        ?int $actorId,
        ?string $note
    ): ?DocumentApproval {
        $approval = $this->activeCycle($approvable);

        if (!$approval) {
            Log::warning('Tidak ada document_approvals berstatus in_progress saat mencoba decideStep.', [
                'approvable_type' => $approvable::class,
                'approvable_id'   => $approvable->getKey(),
                'step_order'      => $stepOrder,
                'target_status'   => $status->value,
            ]);

            return null;
        }

        $step = $approval->steps()->where('step_order', $stepOrder)->first();

        if (!$step) {
            Log::warning('document_approval_steps untuk step_order ini tidak ditemukan pada siklus in_progress.', [
                'approvable_type' => $approvable::class,
                'approvable_id'   => $approvable->getKey(),
                'id_approval'     => $approval->id_approval,
                'step_order'      => $stepOrder,
            ]);

            return null;
        }

        $step->update([
            'status'        => $status,
            'actor_id'      => $actorId,
            'acted_at'      => now(),
            'decision_note' => $note,
        ]);

        if ($status === DocumentApprovalStepStatus::Rejected) {
            $approval->update([
                'status'             => DocumentApprovalStatus::Rejected,
                'current_step_order' => null,
                'completed_at'       => now(),
            ]);

            return $approval;
        }

        $cycleSteps   = $approval->steps;
        $maxStepOrder = $cycleSteps->max('step_order');

        if ($cycleSteps->isEmpty() || $stepOrder === $maxStepOrder) {
            $approval->update([
                'status'             => DocumentApprovalStatus::Approved,
                'current_step_order' => null,
                'completed_at'       => now(),
            ]);

            return $approval;
        }

        $nextStepOrder = $cycleSteps->pluck('step_order')
            ->filter(fn ($order) => $order > $stepOrder)
            ->sort()
            ->first();

        if ($nextStepOrder === null) {
            Log::warning('Step bukan step_order maksimum tapi step berikutnya tidak ada -- cycle ditutup approved.', [
                'approvable_type' => $approvable::class,
                'approvable_id'   => $approvable->getKey(),
                'id_approval'     => $approval->id_approval,
                'step_order'      => $stepOrder,
                'max_step_order'  => $maxStepOrder,
            ]);

            $approval->update([
                'status'             => DocumentApprovalStatus::Approved,
                'current_step_order' => null,
                'completed_at'       => now(),
            ]);

            return $approval;
        }

        $approval->update(['current_step_order' => $nextStepOrder]);

        return $approval;
    }
}
