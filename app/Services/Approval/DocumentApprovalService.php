<?php

namespace App\Services\Approval;

use App\Enums\DocumentApprovalStatus;
use App\Enums\DocumentApprovalStepStatus;
use App\Models\ApprovalTemplate;
use App\Models\DocumentApproval;
use App\Models\DocumentApprovalStep;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class DocumentApprovalService
{
    public function activeTemplate(string $templateCode): ?ApprovalTemplate
    {
        return ApprovalTemplate::with('steps')
            ->where('code', $templateCode)
            ->where('is_active', true)
            ->first();
    }

    public function resolveStepOrderForRole(ApprovalTemplate $template, int $idRole): ?int
    {
        return $template->steps->firstWhere('id_role', $idRole)?->step_order;
    }

    public function startCycle(Model $approvable, string $templateCode, ?array $stepOrders = null): DocumentApproval
    {
        $template = $this->activeTemplate($templateCode);

        if (!$template || $template->steps->isEmpty()) {
            Log::error("Approval template {$templateCode} tidak ditemukan/tidak lengkap saat memulai siklus approval.", [
                'approvable_type' => $approvable::class,
                'approvable_id'   => $approvable->getKey(),
            ]);

            throw new \RuntimeException("Approval template {$templateCode} belum ter-setup dengan benar.");
        }

        $steps = $stepOrders === null
            ? $template->steps
            : $template->steps->whereIn('step_order', $stepOrders);

        if ($steps->isEmpty()) {
            throw new \RuntimeException("stepOrders yang diberikan tidak cocok dengan step manapun di template {$templateCode}.");
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
                'step_order'       => $step->step_order,
                'status'           => DocumentApprovalStepStatus::Pending,
            ]);
        }

        return $approval;
    }

    public function activeCycle(Model $approvable): ?DocumentApproval
    {
        return $approvable->documentApprovals()
            ->with('template.steps')
            ->where('status', DocumentApprovalStatus::InProgress)
            ->latest('id_approval')
            ->first();
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
            Log::warning('Step ini bukan step_order maksimum tapi step berikutnya tidak ditemukan -- cycle ditutup approved untuk mencegah macet.', [
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

    public function cancelActiveCycle(Model $approvable): ?DocumentApproval
    {
        $approval = $this->activeCycle($approvable);

        if (!$approval) {
            return null;
        }

        $approval->update([
            'status'       => DocumentApprovalStatus::Cancelled,
            'completed_at' => now(),
        ]);

        return $approval;
    }
}
