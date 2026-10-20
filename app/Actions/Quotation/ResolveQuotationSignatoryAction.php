<?php

namespace App\Actions\Quotation;

use App\Enums\DocumentApprovalStepStatus;
use App\Enums\QuotationStatus;
use App\Enums\QuotationVerifierRole;
use App\Models\DocumentApprovalStep;
use App\Models\Quotation;

class ResolveQuotationSignatoryAction
{
    /**
     * @return array{name: string, title: string}|null
     */
    public function execute(Quotation $quotation): ?array
    {
        if ($quotation->status !== QuotationStatus::Approved) {
            return null;
        }

        $quotation->loadMissing(['latestDocumentApproval.steps.actor.roles', 'latestDocumentApproval.steps.role']);

        $step = $quotation->latestDocumentApproval?->steps
            ->filter(fn (DocumentApprovalStep $step) => $step->status === DocumentApprovalStepStatus::Approved)
            ->sortByDesc('step_order')
            ->first();

        if ($step === null || $step->actor === null) {
            return $this->fallback();
        }

        return $this->fromStep($step);
    }

    private function fromStep(DocumentApprovalStep $step): array
    {
        $title = QuotationVerifierRole::highestHeldBy($step->actor)?->signatoryTitle()
            ?? QuotationVerifierRole::tryFrom((int) $step->id_role)?->signatoryTitle()
            ?? $step->role?->name
            ?? '';

        return [
            'name'  => $step->actor->name,
            'title' => $title,
        ];
    }

    private function fallback(): array
    {
        $signatory = config('quotation.pdf.fallback_signatory');

        return [
            'name'  => $signatory['name'],
            'title' => $signatory['title'],
        ];
    }
}
