<?php

namespace App\Http\Requests\Customer;

/**
 * Rules identik dengan StoreCustomerLcrRequest -- semua kolom `customer_lcr`
 * nullable (partial update semantics). Pengecualian: `contacts` wajib (min 1 PIC)
 * di create maupun update -- PIC data penting yang harus selalu ada. Extend
 * langsung supaya tidak duplikasi ~90 baris rules.
 */
class UpdateCustomerLcrRequest extends StoreCustomerLcrRequest
{
}
