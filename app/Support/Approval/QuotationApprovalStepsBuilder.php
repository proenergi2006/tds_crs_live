<?php

namespace App\Support\Approval;

use App\Actions\Quotation\ResolveQuotationVerifierAction;
use App\Enums\DocumentApprovalStatus;
use App\Enums\DocumentApprovalStepStatus;
use App\Models\DocumentApproval;
use App\Models\DocumentApprovalStep;
use App\Enums\QuotationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class QuotationApprovalStepsBuilder
{
    public function buildAttempts(Model $quotation): array
    {
        $cycles = $quotation->documentApprovals->sortBy('id_approval')->values();

        if ($cycles->isEmpty()) {
            return [['label' => null, 'steps' => $this->fromLegacyStatus($quotation)]];
        }

        $isDraftNow = $quotation->status === QuotationStatus::Draft;
        $lastIndex = $cycles->count() - 1;

        $groups = $cycles->map(function (DocumentApproval $cycle, int $index) use ($quotation, $isDraftNow, $lastIndex) {
            $wasReopened = $isDraftNow && $index === $lastIndex;

            return ['steps' => $this->fromCycle($cycle, $wasReopened, $quotation)];
        })->values();

        if ($isDraftNow) {
            $groups->push(['steps' => $this->fromVerifierPreview($quotation)]);
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

    private function fromCycle(DocumentApproval $cycle, bool $wasReopened = false, ?Model $quotation = null): array
    {
        $steps = $cycle->steps->map(function (DocumentApprovalStep $step) use ($cycle) {
            $isCurrent = $cycle->status === DocumentApprovalStatus::InProgress
                && $step->step_order === $cycle->current_step_order;

            $stepName = $step->displayName() ?? "Tahap {$step->step_order}";

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
                'timestamp'   => $quotation?->updated_at,
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

    private function fromLegacyStatus(Model $quotation): array
    {
        if ($quotation->status === QuotationStatus::Draft) {
            return $this->fromVerifierPreview($quotation);
        }

        $draft    = ['title' => 'Draft', 'description' => 'Penawaran dibuat dan masih dapat diubah.', 'timestamp' => $quotation->created_at];
        $verified = ['title' => 'Verifikasi', 'description' => 'Menunggu keputusan verifikator.', 'timestamp' => null];

        return match ($quotation->status) {
            QuotationStatus::Approved => [
                $this->step($draft, 'completed'),
                $this->step($verified, 'completed', 'Disetujui. Penawaran final.'),
            ],
            QuotationStatus::Rejected => [
                $this->step($draft, 'completed'),
                $this->step($verified, 'rejected', 'Ditolak oleh verifikator.'),
            ],
            default => [
                $this->step($draft, 'completed'),
                $this->step($verified, 'active'),
            ],
        };
    }

    private function fromVerifierPreview(Model $quotation): array
    {
        $draft = [
            'title'       => 'Draft',
            'description' => 'Penawaran dibuat dan masih dapat diubah.',
            'status'      => 'active',
            'timestamp'   => $quotation->created_at,
        ];

        try {
            $title = 'Verifikasi ' . app(ResolveQuotationVerifierAction::class)->execute($quotation)['verifier']->label();
        } catch (ValidationException) {
            $title = 'Verifikasi';
        }

        return [$draft, [
            'title'       => $title,
            'description' => 'Akan diproses setelah penawaran diajukan.',
            'status'      => 'pending',
            'timestamp'   => null,
        ]];
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
