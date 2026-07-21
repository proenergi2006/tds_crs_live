<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * laravel-nusa-address-full-migration Task 8.1 (opsi a, dikonfirmasi user).
 *
 * Longgarkan id_provinsi/id_kabupaten jadi nullable di customers dan
 * wilayah_angkuts, sejalan dengan filosofi additive yang sudah dipakai di
 * kolom province_id/regency_id/district_id/village_id (Task 2) dan
 * customer_payment.prov_billing/kab_billing (sudah nullable sejak awal).
 *
 * Alasan: tabel referensi lama provinsis (13 baris)/kabupatens (25 baris)
 * tidak lengkap dibanding data BPS resmi (38/514 baris) di provinces/
 * regencies. Selama Customer/Form.vue dan WilayahAngkutForm.vue belum
 * di-cutover penuh ke dropdown BPS (Task 8.1 FE, menyusul), constraint
 * NOT NULL lama akan menolak (422) setiap provinsi/kabupaten BPS yang
 * tidak ada di tabel lama.
 *
 * Ini murni melonggarkan nullability — kolom, FK constraint, dan data
 * existing (74 customers, 27 wilayah_angkuts, semua sudah terisi) tidak
 * disentuh. Di Postgres, NOT NULL adalah constraint kolom yang terpisah
 * dari FK constraint (yang mengikat pada pasangan kolom+referenced table),
 * jadi DROP NOT NULL tidak memerlukan drop/re-add FK constraint apa pun —
 * FK constraint yang sudah ada (customers_id_provinsi_foreign,
 * customers_id_kabupaten_foreign, wilayah_angkuts_id_provinsi_foreign,
 * wilayah_angkuts_id_kabupaten_foreign) tetap utuh setelah migration ini.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->unsignedBigInteger('id_provinsi')->nullable()->change();
            $table->unsignedBigInteger('id_kabupaten')->nullable()->change();
        });

        Schema::table('wilayah_angkuts', function (Blueprint $table) {
            $table->unsignedBigInteger('id_provinsi')->nullable()->change();
            $table->unsignedBigInteger('id_kabupaten')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->unsignedBigInteger('id_provinsi')->nullable(false)->change();
            $table->unsignedBigInteger('id_kabupaten')->nullable(false)->change();
        });

        Schema::table('wilayah_angkuts', function (Blueprint $table) {
            $table->unsignedBigInteger('id_provinsi')->nullable(false)->change();
            $table->unsignedBigInteger('id_kabupaten')->nullable(false)->change();
        });
    }
};
