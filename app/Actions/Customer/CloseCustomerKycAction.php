<?php

namespace App\Actions\Customer;

use App\Enums\CustomerKycStatus;
use App\Models\CustomerCreditSubmission;
use App\Models\CustomerVerification;
use Illuminate\Support\Facades\DB;

class CloseCustomerKycAction
{
    /**
     * Close a KYC cycle: record the final approved credit limit/TOP on the
     * submission and move the verification to `closed`. Not reversible.
     */
    public function execute(CustomerVerification $verification, CustomerCreditSubmission $submission, int $creditLimitApproval, int $topApproval): CustomerCreditSubmission
    {
        return DB::transaction(function () use ($verification, $submission, $creditLimitApproval, $topApproval) {
            $submission->update([
                'credit_limit_approval' => $creditLimitApproval,
                'top_approval'          => $topApproval,
            ]);

            $verification->update(['kyc_status' => CustomerKycStatus::Closed]);

            return $submission->fresh();
        });
    }
}
