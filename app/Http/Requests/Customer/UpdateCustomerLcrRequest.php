<?php

namespace App\Http\Requests\Customer;

/**
 * Rules identik dengan StoreCustomerLcrRequest -- semua kolom `customer_lcr`
 * nullable (partial update semantics), tidak ada field yang jadi required
 * hanya saat create. Extend langsung supaya tidak duplikasi ~90 baris rules.
 */
class UpdateCustomerLcrRequest extends StoreCustomerLcrRequest
{
}
