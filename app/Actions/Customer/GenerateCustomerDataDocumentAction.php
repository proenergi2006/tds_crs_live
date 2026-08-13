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

        // Alamat kantor pusat sekarang jadi salah satu baris di customer_addresses, sama
        // seperti NPWP -- dua-duanya dikeluarkan dari daftar "alamat lainnya" supaya tidak
        // tampil dobel. SiteAddress juga tidak diikutkan, itu punya LCR bukan level customer.
        $otherAddresses = $customer->addresses->reject(
            fn ($a) => in_array($a->address_type, [CustomerAddressType::HeadOffice, CustomerAddressType::RegisteredNpwp, CustomerAddressType::SiteAddress], true)
        );

        $contactsByType = $customer->contacts->groupBy(fn ($c) => $c->contactType?->code ?? 'other');

        return compact('customer', 'headOfficeAddress', 'npwpAddress', 'otherAddresses', 'contactsByType');
    }
}
