<?php

namespace App\Actions\Customer;

use App\Models\CustomerLcr;
use App\Models\CustomerReview;
use App\Models\CustomerVerification;

class GenerateCustomerKycDocumentAction
{
    public function execute(CustomerVerification $verification): array
    {
        $customer = $verification->customer;
        $customer->load(['contacts', 'payment', 'lcr', 'creditRequest', 'headOfficeAddress']);

        $review = CustomerReview::where('id_customer', $verification->id_customer)->first();

        $lcrSites = CustomerLcr::where('id_customer', $verification->id_customer)
            ->with('latestDocumentApproval')
            ->get();

        $creditRequest = $customer->creditRequest;

        $penawarans = $customer->penawarans()->with('items.produk')->get();

        return compact('customer', 'review', 'lcrSites', 'creditRequest', 'verification', 'penawarans');
    }
}
