<?php

namespace App\Actions\Customer;

use App\Enums\CustomerVerificationStatus;
use App\Models\Customer;
use App\Models\CustomerVerification;
use App\Services\CustomerCodeGenerator;
use App\Support\RichText;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DecideCustomerVerificationAction
{
    public function approve(CustomerVerification $verification, int $approvedLimit, int $approvedTop, string $financialReview, array $attachments, int $reviewedBy): CustomerVerification
    {
        $storedAttachments = $this->storeAttachments($verification, $attachments);

        return DB::transaction(function () use ($verification, $approvedLimit, $approvedTop, $financialReview, $storedAttachments, $reviewedBy) {
            $customer = $verification->customer;

            $verification->update([
                'status'              => CustomerVerificationStatus::Approved,
                'approved_limit'      => $approvedLimit,
                'approved_top'        => $approvedTop,
                'financial_review'    => RichText::purify($financialReview),
                'finance_attachments' => $storedAttachments,
                'reviewed_at'         => now(),
                'reviewed_by'         => $reviewedBy,
            ]);

            $this->assignCustomerCode($customer);

            return $verification->fresh();
        });
    }

    private function storeAttachments(CustomerVerification $verification, array $attachments): array
    {
        return array_map(function (UploadedFile $file) use ($verification) {
            $path = $file->storeAs(
                "customer-verifications/{$verification->id_verification}",
                Str::random(20) . '.' . $file->getClientOriginalExtension(),
                'public'
            );

            return ['path' => $path, 'original_name' => $file->getClientOriginalName()];
        }, $attachments);
    }

    private function assignCustomerCode(Customer $customer): void
    {
        if (!empty($customer->customer_code)) {
            return;
        }

        $customer->customer_code = CustomerCodeGenerator::generate();
        $customer->save();
    }

    public function reject(CustomerVerification $verification, string $rejectNote, int $reviewedBy, ?string $financialReview = null, bool $hasFinancialReview = false): CustomerVerification
    {
        return DB::transaction(function () use ($verification, $rejectNote, $reviewedBy, $financialReview, $hasFinancialReview) {
            $attributes = [
                'status'      => CustomerVerificationStatus::Rejected,
                'reject_note' => $rejectNote,
                'reviewed_at' => now(),
                'reviewed_by' => $reviewedBy,
            ];

            if ($hasFinancialReview) {
                $attributes['financial_review'] = RichText::purifyOrNull($financialReview);
            }

            $verification->update($attributes);

            return $verification->fresh();
        });
    }

    public function saveFinancialReviewDraft(CustomerVerification $verification, ?string $financialReview): CustomerVerification
    {
        $verification->update(['financial_review' => RichText::purifyOrNull($financialReview)]);

        return $verification->fresh();
    }
}
