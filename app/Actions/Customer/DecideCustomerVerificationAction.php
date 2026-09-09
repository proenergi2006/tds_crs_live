<?php

namespace App\Actions\Customer;

use App\Enums\CustomerVerificationStatus;
use App\Enums\DocumentApprovalStatus;
use App\Models\Customer;
use App\Models\CustomerLcr;
use App\Models\CustomerVerification;
use Illuminate\Support\Facades\DB;

class DecideCustomerVerificationAction
{
    public function approve(CustomerVerification $verification, int $approvedLimit, int $approvedTop, string $financialReview, int $reviewedBy): CustomerVerification
    {
        return DB::transaction(function () use ($verification, $approvedLimit, $approvedTop, $financialReview, $reviewedBy) {
            if (!$this->lcrApproved($verification->customer)) {
                throw new \RuntimeException('LCR sites are not all approved.');
            }

            $verification->update([
                'status'           => CustomerVerificationStatus::Approved,
                'approved_limit'   => $approvedLimit,
                'approved_top'     => $approvedTop,
                'financial_review' => $financialReview,
                'reviewed_at'      => now(),
                'reviewed_by'      => $reviewedBy,
            ]);

            return $verification->fresh();
        });
    }

    public function reject(CustomerVerification $verification, string $rejectNote, int $reviewedBy): CustomerVerification
    {
        return DB::transaction(function () use ($verification, $rejectNote, $reviewedBy) {
            $verification->update([
                'status'      => CustomerVerificationStatus::Rejected,
                'reject_note' => $rejectNote,
                'reviewed_at' => now(),
                'reviewed_by' => $reviewedBy,
            ]);

            return $verification->fresh();
        });
    }

    public function lcrApproved(Customer $customer): bool
    {
        $sites = $customer->lcr()->with('latestDocumentApproval')->get();

        return $sites->isNotEmpty()
            && $sites->every(fn (CustomerLcr $site) => $site->latestDocumentApproval?->status === DocumentApprovalStatus::Approved);
    }
}
