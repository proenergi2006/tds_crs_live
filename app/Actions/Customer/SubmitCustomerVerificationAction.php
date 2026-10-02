<?php

namespace App\Actions\Customer;

use App\Enums\CustomerVerificationStatus;
use App\Models\Customer;
use App\Models\CustomerVerification;
use Illuminate\Support\Facades\DB;

class SubmitCustomerVerificationAction
{
    public function __construct(private readonly EvaluateCustomerTabCompletenessAction $evaluateTabCompleteness) {}

    public function execute(Customer $customer, int $submittedBy): CustomerVerification
    {
        $isScheduled = (bool) $customer->needs_reverification;
        $creditRequest = $customer->creditRequest;

        return DB::transaction(function () use ($customer, $submittedBy, $isScheduled, $creditRequest) {
            $verification = CustomerVerification::create([
                'id_customer'                => $customer->id_customer,
                'status'                     => CustomerVerificationStatus::InReview,
                'is_scheduled'               => $isScheduled,
                'submitted_at'               => now(),
                'submitted_by'               => $submittedBy,
                'requested_limit_snapshot'   => $creditRequest?->requested_limit,
                'requested_top_snapshot'     => $creditRequest?->requested_top,
                'requested_qty_snapshot'     => $creditRequest?->requested_qty,
                'product_category_snapshot'  => $creditRequest?->product_category,
                'financial_review_snapshot'  => $creditRequest?->financial_review,
            ]);

            return $verification->fresh();
        });
    }

    public function incompleteGroups(Customer $customer): array
    {
        $completeness = $this->evaluateTabCompleteness->execute($customer);

        $creditRequest = $customer->creditRequest;
        $creditFilled = $creditRequest
            && $creditRequest->requested_limit > 0
            && $creditRequest->requested_top !== null
            && $creditRequest->requested_qty > 0
            && $creditRequest->product_category !== null
            && $creditRequest->hasFinancialReview();

        return collect([
            'data_customer' => (bool) $completeness['data_customer'],
            'review'        => (bool) $completeness['review'],
            'credit'        => $creditFilled,
        ])->reject(fn(bool $ok) => $ok)->keys()->all();
    }
}
