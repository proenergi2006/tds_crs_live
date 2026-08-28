<?php

namespace App\Support\Approval;

use App\Actions\Penawaran\Concerns\ResolvesApprovalTemplate;
use App\Enums\DocumentApprovalStatus;
use App\Enums\DocumentApprovalStepStatus;
use App\Models\ApprovalTemplateStep;
use App\Models\DocumentApproval;
use App\Models\DocumentApprovalStep;
use App\Services\Approval\DocumentApprovalService;
use Illuminate\Database\Eloquent\Model;

class PenawaranApprovalStepsBuilder
{
    use ResolvesApprovalTemplate;

    public function buildAttempts(Model $penawaran): array
    {
        $cycles = $penawaran->documentApprovals->sortBy('id_approval')->values();

        if ($cycles->isEmpty()) {
            return [['label' => null, 'steps' => $this->fromLegacyStatus($penawaran)]];
        }

        $isDraftNow = $penawaran->status === 'draft';
        $lastIndex = $cycles->count() - 1;

        $groups = $cycles->map(function (DocumentApproval $cycle, int $index) use ($penawaran, $isDraftNow, $lastIndex) {
            $wasReopened = $isDraftNow && $index === $lastIndex;

            return ['steps' => $this->fromCycle($cycle, $wasReopened, $penawaran)];
        })->values();

        if ($isDraftNow) {
            $groups->push(['steps' => $this->fromTemplatePreview($penawaran)]);
        }

        if ($groups->count() === 1) {
            return [['label' => null, ...$groups->first()]];
        }

        $total = $groups->count();

        return $groups->map(fn (array $group, int $index) => [
            'label' => ($isDraftNow && $index === $total - 1) ? 'Draft Saat Ini' : 'Pengajuan ke-' . ($index + 1),
            'steps' => $group['steps'],
        ])->all();
    }

    private function fromCycle(DocumentApproval $cycle, bool $wasReopened = false, ?Model $penawaran = null): array
    {
        $steps = $cycle->steps->map(function (DocumentApprovalStep $step) use ($cycle) {
            $isCurrent = $cycle->status === DocumentApprovalStatus::InProgress
                && $step->step_order === $cycle->current_step_order;

            $stepName = $step->templateStep?->step_name ?? "Tahap {$step->step_order}";

            return [
                'title'       => $stepName,
                'description' => $this->describeStep($step, $stepName),
                'status'      => match (true) {
                    $step->status === DocumentApprovalStepStatus::Approved => 'completed',
                    $step->status === DocumentApprovalStepStatus::Rejected => 'rejected',
                    $isCurrent => 'active',
                    default => 'pending',
                },
                'timestamp' => $step->acted_at,
            ];
        })->all();

        if ($wasReopened) {
            $steps[] = [
                'title'       => 'Dikembalikan ke Draft',
                'description' => 'Dokumen dikembalikan ke tahap drafting untuk diedit.',
                'status'      => 'rejected',
                'status_text' => 'DIKEMBALIKAN',
                'timestamp'   => $penawaran?->updated_at,
            ];
        }

        return $steps;
    }

    private function describeStep(DocumentApprovalStep $step, string $stepName): string
    {
        $actor = $step->actor?->name;

        if ($step->status === DocumentApprovalStepStatus::Approved) {
            return $actor ? "Disetujui oleh {$actor}." : 'Disetujui.';
        }

        if ($step->status === DocumentApprovalStepStatus::Rejected) {
            $base = $actor ? "Ditolak oleh {$actor}." : 'Ditolak.';
            return $step->decision_note ? "{$base} Catatan: {$step->decision_note}" : $base;
        }

        return "Menunggu keputusan {$stepName}.";
    }

    private function fromLegacyStatus(Model $penawaran): array
    {
        if ($penawaran->status === 'draft') {
            return $this->fromTemplatePreview($penawaran);
        }

        $draft = ['title' => 'Draft', 'description' => 'Penawaran dibuat dan masih dapat diubah.', 'timestamp' => $penawaran->created_at];
        $waitingBm = ['title' => 'Waiting BM', 'description' => 'Menunggu verifikasi dari Branch Manager.', 'timestamp' => null];
        $approvedBm = ['title' => 'Approved BM', 'description' => 'Disetujui Branch Manager, diteruskan ke Operations Manager.', 'timestamp' => null];
        $approvedOm = ['title' => 'Approved OM', 'description' => 'Disetujui Operations Manager. Penawaran final.', 'timestamp' => null];

        return match ($penawaran->status) {
            'approved_om' => [
                $this->step($draft, 'completed'),
                $this->step($waitingBm, 'completed'),
                $this->step($approvedBm, 'completed'),
                $this->step($approvedOm, 'completed'),
            ],
            'approved_bm' => [
                $this->step($draft, 'completed'),
                $this->step($waitingBm, 'completed'),
                $this->step($approvedBm, 'completed'),
                $this->step($approvedOm, 'active'),
            ],
            'waiting_branch_manager' => [
                $this->step($draft, 'completed'),
                $this->step($waitingBm, 'active'),
                $this->step($approvedBm, 'pending'),
                $this->step($approvedOm, 'pending'),
            ],
            'rejected_bm' => [
                $this->step($draft, 'completed'),
                $this->step($waitingBm, 'rejected', 'Ditolak oleh Branch Manager.'),
                $this->step($approvedBm, 'pending'),
                $this->step($approvedOm, 'pending'),
            ],
            'rejected_om' => [
                $this->step($draft, 'completed'),
                $this->step($waitingBm, 'completed'),
                $this->step($approvedBm, 'completed'),
                $this->step($approvedOm, 'rejected', 'Ditolak oleh Operations Manager.'),
            ],
            default => [
                $this->step($draft, 'active'),
                $this->step($waitingBm, 'pending'),
                $this->step($approvedBm, 'pending'),
                $this->step($approvedOm, 'pending'),
            ],
        };
    }

    private function fromTemplatePreview(Model $penawaran): array
    {
        $draft = [
            'title'       => 'Draft',
            'description' => 'Penawaran dibuat dan masih dapat diubah.',
            'status'      => 'active',
            'timestamp'   => $penawaran->created_at,
        ];

        $template = (new DocumentApprovalService())->activeTemplate($this->templateCodeFor($penawaran));

        if (!$template) {
            return [$draft];
        }

        $templateSteps = $template->steps->map(fn (ApprovalTemplateStep $step) => [
            'title'       => $step->step_name,
            'description' => 'Akan diproses setelah penawaran diajukan.',
            'status'      => 'pending',
            'timestamp'   => null,
        ])->all();

        return [$draft, ...$templateSteps];
    }

    private function step(array $base, string $status, ?string $descriptionOverride = null): array
    {
        return [
            'title'       => $base['title'],
            'description' => $descriptionOverride ?? $base['description'],
            'status'      => $status,
            'timestamp'   => $base['timestamp'],
        ];
    }
}
