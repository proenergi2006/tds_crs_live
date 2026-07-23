<?php

namespace App\Services\Approval;

use App\Enums\DocumentApprovalStatus;
use App\Enums\DocumentApprovalStepStatus;
use App\Models\ApprovalTemplate;
use App\Models\DocumentApproval;
use App\Models\DocumentApprovalStep;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * Mesin generik untuk siklus approval polymorphic `document_approvals` /
 * `document_approval_steps`. $approvable WAJIB sudah punya relasi
 * `documentApprovals()` (morphMany ke DocumentApproval, keyed `approvable_id`
 * pakai primary key model itu sendiri) -- lihat CustomerVerification untuk
 * contoh relasi yang dibutuhkan.
 *
 * Diekstrak sebagai Service (bukan private method di controller) karena
 * cluster logic ini genuinely berdiri sendiri sebagai state machine approval,
 * dipakai lintas domain (customer_verification, customer_lcr_survey, dst).
 */
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

    /**
     * Mulai siklus approval baru untuk $approvable. Morph relation mendukung
     * banyak siklus per approvable (mis. re-submit setelah reject), jadi
     * method ini SELALU membuat row baru, tidak reuse siklus lama.
     */
    public function startCycle(Model $approvable, string $templateCode): DocumentApproval
    {
        $template = $this->activeTemplate($templateCode);

        if (!$template || $template->steps->isEmpty()) {
            Log::error("Approval template {$templateCode} tidak ditemukan/tidak lengkap saat memulai siklus approval.", [
                'approvable_type' => $approvable::class,
                'approvable_id'   => $approvable->getKey(),
            ]);

            throw new \RuntimeException("Approval template {$templateCode} belum ter-setup dengan benar.");
        }

        $approval = $approvable->documentApprovals()->create([
            'id_template'        => $template->id_template,
            'status'             => DocumentApprovalStatus::InProgress,
            'current_step_order' => $template->steps->min('step_order'),
            'started_at'         => now(),
        ]);

        foreach ($template->steps as $step) {
            DocumentApprovalStep::create([
                'id_approval'      => $approval->id_approval,
                'id_template_step' => $step->id_step,
                'step_order'       => $step->step_order,
                'status'           => DocumentApprovalStepStatus::Pending,
            ]);
        }

        return $approval;
    }

    /**
     * Siklus approval TERBARU yang masih in_progress untuk $approvable, atau
     * null kalau tidak ada.
     */
    public function activeCycle(Model $approvable): ?DocumentApproval
    {
        return $approvable->documentApprovals()
            ->with('template.steps')
            ->where('status', DocumentApprovalStatus::InProgress)
            ->latest('id_approval')
            ->first();
    }

    /**
     * Putuskan 1 step (approve/reject) pada siklus in_progress TERBARU milik
     * $approvable. Kalau step yang diputuskan adalah step_order maksimum
     * (atau reject di step manapun), siklus ditutup. Kalau bukan, siklus maju
     * ke step_order berikutnya.
     */
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

        $templateSteps = $approval->template?->steps ?? collect();
        $maxStepOrder  = $templateSteps->max('step_order');

        if ($templateSteps->isEmpty() || $stepOrder === $maxStepOrder) {
            $approval->update([
                'status'             => DocumentApprovalStatus::Approved,
                'current_step_order' => null,
                'completed_at'       => now(),
            ]);

            return $approval;
        }

        $nextStepOrder = $templateSteps->pluck('step_order')
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
}
