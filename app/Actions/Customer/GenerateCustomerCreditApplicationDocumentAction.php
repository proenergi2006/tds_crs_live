<?php

namespace App\Actions\Customer;

use App\Enums\CustomerAddressType;
use App\Models\CustomerVerification;

class GenerateCustomerCreditApplicationDocumentAction
{
    public function execute(CustomerVerification $verification): array
    {
        $customer = $verification->customer;

        $customer->load(['user:id,name', 'addresses.province', 'addresses.regency', 'addresses.district', 'addresses.village']);

        $headOfficeAddress = $customer->addresses->first(
            fn($a) => $a->address_type === CustomerAddressType::HeadOffice
        );

        $marketingName = $customer->user?->name ?? '-';

        return compact('customer', 'verification', 'headOfficeAddress', 'marketingName');
    }
}
