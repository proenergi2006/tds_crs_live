<?php

namespace App\Actions\Customer;

use App\Enums\CustomerVerificationStatus;
use App\Models\Customer;
use App\Models\CustomerVerification;
use App\Services\CustomerCodeGenerator;
use Illuminate\Support\Facades\DB;

class DecideCustomerVerificationAction
{
    public function approve(CustomerVerification $verification, int $approvedLimit, int $approvedTop, string $financialReview, int $reviewedBy): CustomerVerification
    {
        return DB::transaction(function () use ($verification, $approvedLimit, $approvedTop, $financialReview, $reviewedBy) {
            $customer = $verification->customer;

            $verification->update([
                'status'           => CustomerVerificationStatus::Approved,
                'approved_limit'   => $approvedLimit,
                'approved_top'     => $approvedTop,
                'financial_review' => $financialReview,
                'reviewed_at'      => now(),
                'reviewed_by'      => $reviewedBy,
            ]);

            $this->assignCustomerCode($customer);

            return $verification->fresh();
        });
    }

    private function assignCustomerCode(Customer $customer): void
    {
        if (!empty($customer->customer_code)) {
            return;
        }

        $customer->customer_code = CustomerCodeGenerator::generate();
        $customer->save();
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
}
