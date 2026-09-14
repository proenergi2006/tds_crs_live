<?php

namespace App\Actions\Customer;

use App\Enums\CustomerAddressType;
use App\Models\Customer;

class GenerateCustomerDataDocumentAction
{
    public function execute(Customer $customer): array
    {
        $customer->load([
            'user',
            'addresses.province', 'addresses.regency', 'addresses.district', 'addresses.village',
            'contacts',
            'payment',
        ]);

        $headOfficeAddress = $customer->addresses->first(
            fn ($a) => $a->address_type === CustomerAddressType::HeadOffice
        );

        $npwpAddress = $customer->addresses->first(
            fn ($a) => $a->address_type === CustomerAddressType::RegisteredNpwp
        );

        $otherAddresses = $customer->addresses->reject(
            fn ($a) => in_array($a->address_type, [CustomerAddressType::HeadOffice, CustomerAddressType::RegisteredNpwp, CustomerAddressType::SiteAddress], true)
        );

        $contactsByType = collect(['other' => $customer->contacts]);

        return compact('customer', 'headOfficeAddress', 'npwpAddress', 'otherAddresses', 'contactsByType');
    }
}
