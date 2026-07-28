<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * `customer_payment`'s billing-address columns historically held the
     * customer's NPWP-registered address (fed from the public form's
     * "registered" section, see CustomerVerificationController). Carried
     * over as `registered_npwp` in `customer_addresses`, not `billing`
     * (that type is reserved for invoice delivery address, already migrated
     * separately in Fase 2). `email_billing`/`kecamatan_billing`/
     * `kelurahan_billing` have no equivalent column in `customer_addresses`
     * and are intentionally not carried over.
     */
    public function up(): void
    {
        $rows = DB::table('customer_payment')
            ->whereNotNull('alamat_billing')
            ->where('alamat_billing', '!=', '')
            ->get();

        if ($rows->isEmpty()) {
            return;
        }

        $now = now();

        $addressRows = $rows->map(fn ($row) => [
            'id_customer'  => $row->id_customer,
            'address_type' => 'registered_npwp',
            'address_line' => $row->alamat_billing,
            'province_id'  => $row->province_id,
            'regency_id'   => $row->regency_id,
            'district_id'  => $row->district_id,
            'village_id'   => $row->village_id,
            'postal_code'  => $row->postalcode_billing,
            'is_primary'   => true,
            'created_at'   => $now,
            'updated_at'   => $now,
        ])->all();

        DB::table('customer_addresses')->insert($addressRows);
    }

    public function down(): void
    {
        DB::table('customer_addresses')->where('address_type', 'registered_npwp')->delete();
    }
};
