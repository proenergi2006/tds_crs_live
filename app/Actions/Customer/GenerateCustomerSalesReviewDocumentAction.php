<?php

namespace App\Actions\Customer;

use App\Models\CustomerReview;
use App\Models\CustomerVerification;

class GenerateCustomerSalesReviewDocumentAction
{
    public function execute(CustomerVerification $verification): array
    {
        $customer = $verification->customer;
        $review = CustomerReview::where('id_customer', $verification->id_customer)->first();

        return compact('customer', 'review');
    }
}
