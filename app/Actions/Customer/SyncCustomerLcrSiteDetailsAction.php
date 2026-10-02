<?php

namespace App\Actions\Customer;

use App\Enums\CustomerAddressType;
use App\Models\CustomerAddress;
use App\Models\CustomerContact;
use App\Models\CustomerLcr;

class SyncCustomerLcrSiteDetailsAction
{
    public function execute(CustomerLcr $site, ?array $addressData, ?array $contactData): void
    {
        if ($addressData !== null) {
            CustomerAddress::updateOrCreate(
                ['id_lcr' => $site->id_lcr],
                [
                    'id_customer'  => $site->id_customer,
                    'address_type' => CustomerAddressType::SiteAddress,
                    'address_line' => $addressData['address_line'] ?? null,
                    'province_id'  => $addressData['province_id'] ?? null,
                    'regency_id'   => $addressData['regency_id'] ?? null,
                    'district_id'  => $addressData['district_id'] ?? null,
                    'village_id'   => $addressData['village_id'] ?? null,
                    'postal_code'  => $addressData['postal_code'] ?? null,
                ]
            );
        }

        if ($contactData !== null) {
            CustomerContact::updateOrCreate(
                ['id_lcr' => $site->id_lcr],
                [
                    'id_customer' => $site->id_customer,
                    'full_name'   => $contactData['full_name'],
                    'position'    => $contactData['position'] ?? null,
                    'phone'       => $contactData['phone'] ?? null,
                    'mobile'      => $contactData['mobile'] ?? null,
                    'email'       => $contactData['email'] ?? null,
                ]
            );
        }
    }
}
