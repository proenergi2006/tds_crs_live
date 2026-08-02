<?php

namespace App\Actions\Customer;

use App\Enums\CustomerAddressType;
use App\Models\Customer;

class GenerateCustomerDataDocumentAction
{
    /**
     * Kumpulin data buat print "Data Customer" yang berdiri sendiri -- read-only,
     * ngikutin persis apa yang udah ditampilin CustomerDataTab.vue (info korporat,
     * alamat NPWP, kontak PIC per tipe, payment). Logistik gak di-load di sini,
     * itu referensi LCR aja dan gak dicetak di dokumen ini.
     */
    public function execute(Customer $customer): array
    {
        $customer->load([
            'user',
            'province', 'regency', 'district', 'village',
            'addresses.province', 'addresses.regency', 'addresses.district', 'addresses.village',
            'contacts.contactType',
            'payment',
        ]);

        $npwpAddress = $customer->addresses->first(
            fn ($a) => $a->address_type === CustomerAddressType::RegisteredNpwp
        );

        // "Alamat lainnya" ini di luar Head Office (kolomnya langsung di Customer,
        // bukan baris di customer_addresses) dan NPWP (udah dipisah di atas).
        // SiteAddress gak diikutkan, itu punya LCR bukan level customer.
        $otherAddresses = $customer->addresses->reject(
            fn ($a) => in_array($a->address_type, [CustomerAddressType::RegisteredNpwp, CustomerAddressType::SiteAddress], true)
        );

        $contactsByType = $customer->contacts->groupBy(fn ($c) => $c->contactType?->code ?? 'other');

        return compact('customer', 'npwpAddress', 'otherAddresses', 'contactsByType');
    }
}
