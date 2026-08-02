<?php

namespace App\Actions\Customer;

use App\Enums\CustomerKycStatus;
use App\Models\CustomerCreditSubmission;
use App\Models\CustomerVerification;
use Illuminate\Support\Facades\DB;

class CloseCustomerKycAction
{
    /**
     * Closes out a KYC cycle -- saves the final approved credit limit/TOP on
     * the submission and flips the verification to `closed`. Can't be undone.
     */
    public function execute(CustomerVerification $verification, CustomerCreditSubmission $submission, int $creditLimitApproval, int $topApproval, ?string $financialReview): CustomerCreditSubmission
    {
        return DB::transaction(function () use ($verification, $submission, $creditLimitApproval, $topApproval, $financialReview) {
            $submission->update([
                'credit_limit_approval' => $creditLimitApproval,
                'top_approval'          => $topApproval,
                'financial_review'      => $financialReview,
            ]);

            $verification->update(['kyc_status' => CustomerKycStatus::Closed]);

            return $submission->fresh();
        });
    }
}
