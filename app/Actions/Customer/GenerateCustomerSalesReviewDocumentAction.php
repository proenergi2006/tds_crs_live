<?php

namespace App\Actions\Customer;

use App\Models\CustomerReview;
use App\Models\CustomerVerification;

class GenerateCustomerSalesReviewDocumentAction
{
    public function __construct(private ResolveVerificationSupervisorNameAction $resolveSupervisorName)
    {
    }

    public function execute(CustomerVerification $verification): array
    {
        $customer = $verification->customer->loadMissing('user:id,name');
        $marketingName = $customer->user?->name ?? '-';
        $supervisorName = $this->resolveSupervisorName->execute($verification);
        $review = CustomerReview::where('id_customer', $verification->id_customer)->first();

        return compact('customer', 'review', 'marketingName', 'supervisorName');
    }
}
