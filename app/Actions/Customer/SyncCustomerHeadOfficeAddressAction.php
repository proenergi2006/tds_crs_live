<?php

namespace App\Actions\Customer;

use App\Enums\CustomerAddressType;
use App\Models\CustomerAddress;

// satu-satunya titik tulis alamat kantor pusat, dipanggil dari create/update manual & submit onboarding biar format address_line konsisten
class SyncCustomerHeadOfficeAddressAction
{
    public function execute(int $customerId, array $address): CustomerAddress
    {
        $addressLine = $this->buildAddressLine(
            $address['company_address'] ?? null,
            $address['customer_sub_district'] ?? null,
            $address['customer_village'] ?? null
        );

        return CustomerAddress::updateOrCreate(
            ['id_customer' => $customerId, 'address_type' => CustomerAddressType::HeadOffice],
            [
                'address_line' => $addressLine,
                'province_id'  => $address['province_id'] ?? null,
                'regency_id'   => $address['regency_id'] ?? null,
                'district_id'  => $address['district_id'] ?? null,
                'village_id'   => $address['village_id'] ?? null,
                'postal_code'  => $address['postal_code'] ?? null,
            ]
        );
    }

    // customer_sub_district/customer_village string bebas pra-BPS, gak ada kolom padanan di customer_addresses jadi digabung ke address_line
    private function buildAddressLine(?string $companyAddress, ?string $subDistrict, ?string $village): string
    {
        $parts = [];

        if ($companyAddress !== null && trim($companyAddress) !== '') {
            $parts[] = trim($companyAddress);
        }

        if ($subDistrict !== null && trim($subDistrict) !== '') {
            $parts[] = 'Kec. '.trim($subDistrict);
        }

        if ($village !== null && trim($village) !== '') {
            $parts[] = 'Kel. '.trim($village);
        }

        return implode(', ', $parts);
    }
}
