<?php

namespace App\Actions\Quotation;

use App\Enums\QuotationStatus;
use App\Models\Quotation;

class ResolveQuotationVerificationAction
{
    public function execute(string $token): ?array
    {
        $quotation = Quotation::where('token_verifikasi', $token)->with('latestDocumentApproval.steps')->first();

        return $quotation ? $this->buildResult($quotation) : null;
    }

    private function buildResult(Quotation $quotation): array
    {
        if ($quotation->status !== QuotationStatus::Approved) {
            return ['verified' => false];
        }

        return [
            'verified' => true,
            'nomor_penawaran' => $quotation->nomor_penawaran,
            'status_label' => 'Terverifikasi',
            'tanggal_approval' => $quotation->finalApprovedAt(),
        ];
    }
}
