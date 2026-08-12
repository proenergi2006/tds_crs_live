<?php

namespace App\Support\Approval;

use App\Enums\DocumentApprovalStatus;
use App\Enums\DocumentApprovalStepStatus;
use App\Models\DocumentApproval;
use App\Models\DocumentApprovalStep;
use Illuminate\Database\Eloquent\Model;

// tiap submit bikin cycle DocumentApproval baru (gak reuse) -- kalo gak ada cycle sama sekali, fallback ke kolom legacy
class PenawaranApprovalStepsBuilder
{
    public function buildAttempts(Model $penawaran): array
    {
        $cycles = $penawaran->documentApprovals->sortBy('id_approval')->values();

        if ($cycles->isEmpty()) {
            return [['label' => null, 'steps' => $this->fromLegacyStatus($penawaran)]];
        }

        if ($cycles->count() === 1) {
            return [['label' => null, 'steps' => $this->fromCycle($cycles->first())]];
        }

        return $cycles->map(fn (DocumentApproval $cycle, int $index) => [
            'label' => 'Pengajuan ke-' . ($index + 1),
            'steps' => $this->fromCycle($cycle),
        ])->values()->all();
    }

    private function fromCycle(DocumentApproval $cycle): array
    {
        return $cycle->steps->map(function (DocumentApprovalStep $step) use ($cycle) {
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
        $draft = ['title' => 'Draft', 'description' => 'Penawaran dibuat dan masih dapat diubah.', 'timestamp' => $penawaran->created_at];
        $waitingBm = ['title' => 'Waiting BM', 'description' => 'Menunggu verifikasi dari Branch Manager.', 'timestamp' => null];
        $approvedBm = ['title' => 'Approved BM', 'description' => 'Disetujui Branch Manager, diteruskan ke Operations Manager.', 'timestamp' => $penawaran->bm_tanggal];
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
