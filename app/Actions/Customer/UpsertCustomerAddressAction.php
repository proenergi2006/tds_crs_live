<?php

namespace App\Actions\Customer;

use App\Enums\CustomerAddressType;
use App\Models\CustomerAddress;

// satu-satunya titik tulis customer_addresses per (customer, type), dipakai untuk head_office, npwp, dan tipe lain lewat updateAddress()
class UpsertCustomerAddressAction
{
    public function execute(int $customerId, CustomerAddressType $type, array $data): CustomerAddress
    {
        return CustomerAddress::updateOrCreate(
            ['id_customer' => $customerId, 'address_type' => $type],
            [
                'address_line' => $data['address_line'] ?? null,
                'province_id'  => $data['province_id'] ?? null,
                'regency_id'   => $data['regency_id'] ?? null,
                'district_id'  => $data['district_id'] ?? null,
                'village_id'   => $data['village_id'] ?? null,
                'postal_code'  => $data['postal_code'] ?? null,
                'is_primary'   => true,
            ]
        );
    }
}
