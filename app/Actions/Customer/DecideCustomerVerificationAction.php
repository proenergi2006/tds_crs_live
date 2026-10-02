<?php

namespace App\Actions\Customer;

use App\Enums\CustomerVerificationStatus;
use App\Models\Customer;
use App\Models\CustomerCreditRequest;
use App\Models\CustomerVerification;
use App\Services\CustomerCodeGenerator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Mews\Purifier\Facades\Purifier;

class DecideCustomerVerificationAction
{
    public function approve(CustomerVerification $verification, int $approvedLimit, int $approvedTop, ?string $notes, array $attachments, int $reviewedBy): CustomerVerification
    {
        $storedAttachments = $this->storeAttachments($verification, $attachments);

        return DB::transaction(function () use ($verification, $approvedLimit, $approvedTop, $notes, $storedAttachments, $reviewedBy) {
            $customer = $verification->customer;

            $verification->update([
                'status'              => CustomerVerificationStatus::Approved,
                'approved_limit'      => $approvedLimit,
                'approved_top'        => $approvedTop,
                'notes'               => filled($notes) ? Purifier::clean($notes, 'ckeditor') : null,
                'finance_attachments' => $storedAttachments,
                'reviewed_at'         => now(),
                'reviewed_by'         => $reviewedBy,
            ]);

            $this->assignCustomerCode($customer);

            CustomerCreditRequest::where('id_customer', $customer->id_customer)->update(['financial_review' => null]);

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
