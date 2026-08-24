<?php

namespace App\Actions\Customer;

use App\Enums\CustomerAddressType;
use App\Models\CustomerAddress;
use App\Models\CustomerContact;
use App\Models\CustomerLcr;

class SyncCustomerLcrSiteDetailsAction
{
    public function execute(CustomerLcr $site, ?array $addressData, ?array $contactsData): void
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

        if ($contactsData !== null) {
            $this->syncContacts($site, $contactsData);
        }
    }

    private function syncContacts(CustomerLcr $site, array $contactsData): void
    {
        $existingIds = CustomerContact::where('id_lcr', $site->id_lcr)
            ->pluck('id_contact')
            ->all();

        $incomingIds = collect($contactsData)->pluck('id_contact')->filter()->all();

        $idsToDelete = array_diff($existingIds, $incomingIds);

        if (!empty($idsToDelete)) {
            CustomerContact::where('id_lcr', $site->id_lcr)
                ->whereIn('id_contact', $idsToDelete)
                ->delete();
        }

        foreach ($contactsData as $contact) {
            $attributes = [
                'full_name' => $contact['full_name'],
                'position'  => $contact['position'] ?? null,
                'phone'     => $contact['phone'] ?? null,
                'mobile'    => $contact['mobile'] ?? null,
                'email'     => $contact['email'] ?? null,
            ];

            if (!empty($contact['id_contact'])) {
                CustomerContact::where('id_contact', $contact['id_contact'])
                    ->where('id_lcr', $site->id_lcr)
                    ->update($attributes);

                continue;
            }

            CustomerContact::create([
                ...$attributes,
                'id_customer' => $site->id_customer,
                'id_lcr'      => $site->id_lcr,
            ]);
        }
    }
}
