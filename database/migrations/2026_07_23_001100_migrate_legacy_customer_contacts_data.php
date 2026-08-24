<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Mapping blok PIC lama -> customer_contact_types.code. "decision" (pengambil
     * keputusan pembelian) dipetakan ke director; "ordering" (yang menempatkan
     * order) ke procurement; "billing" dan "invoice" keduanya urusan
     * penagihan/finance sehingga dipetakan ke finance yang sama (tidak ada tipe
     * terpisah untuk keduanya di 4 tipe kontak yang di-seed). pic_fuelman_*
     * tidak dipetakan sama sekali -- di-drop total, tidak ada tipe kontak
     * padanannya.
     */
    private const TYPE_MAP = [
        'decision' => 'director',
        'ordering' => 'procurement',
        'billing'  => 'finance',
        'invoice'  => 'finance',
    ];

    public function up(): void
    {
        $legacyRows = DB::table('customer_contacts_legacy')->get();

        // Tabel legacy kosong (DB fresh, dev/test) = gak ada apapun buat dipindah -- skip guard
        // seeder di bawah, biar migrate:fresh gak selalu butuh CustomerContactTypeSeeder duluan.
        if ($legacyRows->isEmpty()) {
            return;
        }

        $contactTypeIds = DB::table('customer_contact_types')->pluck('id', 'code');

        foreach (array_unique(self::TYPE_MAP) as $code) {
            if (!isset($contactTypeIds[$code])) {
                throw new \RuntimeException(
                    "customer_contact_types row with code={$code} not found. " .
                        'Run CustomerContactTypeSeeder before this migration.'
                );
            }
        }

        $now = now();
        $contactRows = [];
        $addressRows = [];

        foreach ($legacyRows as $row) {
            foreach (self::TYPE_MAP as $block => $code) {
                $name     = trim((string) ($row->{"pic_{$block}_name"} ?? ''));
                $position = trim((string) ($row->{"pic_{$block}_position"} ?? ''));
                $phone    = trim((string) ($row->{"pic_{$block}_telp"} ?? ''));
                $mobile   = trim((string) ($row->{"pic_{$block}_mobile"} ?? ''));
                $email    = trim((string) ($row->{"pic_{$block}_email"} ?? ''));

                if ($name === '' && $position === '' && $phone === '' && $mobile === '' && $email === '') {
                    continue;
                }

                $contactRows[] = [
                    'id_customer'     => $row->id_customer,
                    'id_contact_type' => $contactTypeIds[$code],
                    'id_lcr'          => null,
                    'full_name'       => $name !== '' ? $name : null,
                    'position'        => $position !== '' ? $position : null,
                    'phone'           => $phone !== '' ? $phone : null,
                    'mobile'          => $mobile !== '' ? $mobile : null,
                    'email'           => $email !== '' ? $email : null,
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ];
            }

            // product_delivery_address sengaja tidak dimigrasikan kemanapun
            // (drop total, redundan dengan alamat site di customer_lcr).
            $primary   = trim((string) ($row->invoice_delivery_addr_primary ?? ''));
            $secondary = trim((string) ($row->invoice_delivery_addr_secondary ?? ''));

            if ($primary !== '') {
                $addressRows[] = [
                    'id_customer'  => $row->id_customer,
                    'address_type' => 'billing',
                    'address_line' => $primary,
                    'is_primary'   => true,
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ];
            }

            if ($secondary !== '') {
                $addressRows[] = [
                    'id_customer'  => $row->id_customer,
                    'address_type' => 'billing',
                    'address_line' => $secondary,
                    'is_primary'   => false,
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ];
            }
        }

        if (!empty($contactRows)) {
            DB::table('customer_contacts')->insert($contactRows);
        }

        if (!empty($addressRows)) {
            DB::table('customer_addresses')->insert($addressRows);
        }
    }

    public function down(): void
    {
        //
    }
};
