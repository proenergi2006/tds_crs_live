<?php

namespace App\Actions\Customer;

use App\Enums\CustomerAddressType;
use App\Models\CustomerVerification;

class GenerateCustomerCreditApplicationDocumentAction
{
    public function execute(CustomerVerification $verification): array
    {
        $customer = $verification->customer;
        $creditRequest = $customer->creditRequest;

        $customer->load(['addresses.province', 'addresses.regency', 'addresses.district', 'addresses.village']);

        $headOfficeAddress = $customer->addresses->first(
            fn ($a) => $a->address_type === CustomerAddressType::HeadOffice
        );

        return compact('customer', 'creditRequest', 'verification', 'headOfficeAddress');
    }
}
