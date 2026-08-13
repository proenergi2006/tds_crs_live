<?php

namespace App\Actions\Customer;

use App\Enums\CustomerAddressType;
use App\Models\Customer;

class GenerateCustomerDataDocumentAction
{
    // data buat print "Data Customer" read-only, ngikutin CustomerDataTab.vue; logistik gak diikutin, itu punya LCR

    public function execute(Customer $customer): array
    {
        $customer->load([
            'user',
            'addresses.province', 'addresses.regency', 'addresses.district', 'addresses.village',
            'contacts.contactType',
            'payment',
        ]);

        $headOfficeAddress = $customer->addresses->first(
            fn ($a) => $a->address_type === CustomerAddressType::HeadOffice
        );

        $npwpAddress = $customer->addresses->first(
            fn ($a) => $a->address_type === CustomerAddressType::RegisteredNpwp
        );

        // head office & NPWP dikeluarkan dari "alamat lainnya" biar gak dobel; SiteAddress punya LCR, bukan level customer
        $otherAddresses = $customer->addresses->reject(
            fn ($a) => in_array($a->address_type, [CustomerAddressType::HeadOffice, CustomerAddressType::RegisteredNpwp, CustomerAddressType::SiteAddress], true)
        );

        $contactsByType = $customer->contacts->groupBy(fn ($c) => $c->contactType?->code ?? 'other');

        return compact('customer', 'headOfficeAddress', 'npwpAddress', 'otherAddresses', 'contactsByType');
    }
}
