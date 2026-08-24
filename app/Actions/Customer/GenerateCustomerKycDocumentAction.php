<?php

namespace App\Actions\Customer;

use App\Models\CustomerLcr;
use App\Models\CustomerReview;
use App\Models\CustomerVerification;

class GenerateCustomerKycDocumentAction
{
    // ngumpulin data (Data Customer, Sales Review, LCR, Credit Application, Penawaran Lookup) buat KYC PDF, read-only

    public function execute(CustomerVerification $verification): array
    {
        $customer = $verification->customer;
        $customer->load(['contacts', 'payment', 'lcr', 'creditSubmissions', 'headOfficeAddress']);

        $review = CustomerReview::where('id_customer', $verification->id_customer)->first();

        $lcrSites = CustomerLcr::where('id_customer', $verification->id_customer)
            ->with('latestDocumentApproval')
            ->get();

        $submission = $customer->latestCreditSubmission;

        $penawarans = $customer->penawarans()->with('items.produk')->get();

        return compact('customer', 'review', 'lcrSites', 'submission', 'penawarans');
    }
}
