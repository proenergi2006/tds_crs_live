<?php
// database/migrations/2025_08_15_000000_add_extended_fields_to_customers_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            // string NOT NULL → beri default '' supaya aman saat alter di table yang sudah berisi data
            $table->string('kode_pelanggan', 50)->default('');
            $table->string('website_customer', 50)->default('');

            // tinyint → tinyInteger
            $table->tinyInteger('tipe_bisnis')->default(0);
            $table->string('tipe_bisnis_lain', 300)->nullable();

            $table->tinyInteger('ownership')->default(0);
            $table->string('ownership_lain', 300)->nullable();

            $table->string('nomor_sertifikat', 300)->nullable();
            $table->string('nomor_sertifikat_file', 300)->nullable();

            $table->string('nomor_npwp', 300)->nullable();
            $table->string('nomor_npwp_file', 300)->nullable();

            $table->string('nomor_siup', 300)->nullable();
            $table->string('nomor_siup_file', 300)->nullable();

            $table->string('nomor_tdp', 300)->nullable();
            $table->string('nomor_tdp_file', 300)->nullable();

            $table->string('dokumen_lainnya', 300)->nullable();
            $table->text('dokumen_lainnya_file')->nullable();

            $table->tinyInteger('need_update')->default(0);
            $table->tinyInteger('is_generated_link')->default(0);
            $table->integer('count_update')->default(0);
            $table->tinyInteger('is_verified')->default(0);
            $table->tinyInteger('status_customer')->default(1);

            $table->date('prospect_customer_date')->nullable();
            $table->tinyInteger('prospect_evaluated')->default(0);

            $table->date('fix_customer_since')->nullable();
            $table->date('fix_customer_redate')->nullable();

            $table->string('jenis_payment', 20)->default(''); // NOT NULL → default ''
            $table->integer('top_payment')->nullable();
            $table->integer('jenis_net')->nullable();

            // Di PostgreSQL tidak ada unsigned; ini tetap bigint.
            $table->unsignedBigInteger('credit_limit')->default(0);
            $table->unsignedBigInteger('credit_limit_diajukan')->default(0);

            $table->integer('id_verification')->nullable();
            $table->tinyInteger('ajukan')->default(0);

            $table->string('induk_perusahaan', 300)->nullable();
            $table->string('kecamatan_customer', 300)->nullable();
            $table->string('kelurahan_customer', 300)->nullable();

            $table->text('print_product')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn([
                'kode_pelanggan',
                'website_customer',
                'tipe_bisnis',
                'tipe_bisnis_lain',
                'ownership',
                'ownership_lain',
                'nomor_sertifikat',
                'nomor_sertifikat_file',
                'nomor_npwp',
                'nomor_npwp_file',
                'nomor_siup',
                'nomor_siup_file',
                'nomor_tdp',
                'nomor_tdp_file',
                'dokumen_lainnya',
                'dokumen_lainnya_file',
                'need_update',
                'is_generated_link',
                'count_update',
                'is_verified',
                'status_customer',
                'prospect_customer_date',
                'prospect_evaluated',
                'fix_customer_since',
                'fix_customer_redate',
                'jenis_payment',
                'top_payment',
                'jenis_net',
                'credit_limit',
                'credit_limit_diajukan',
                'id_verification',
                'ajukan',
                'induk_perusahaan',
                'kecamatan_customer',
                'kelurahan_customer',
                'print_product',
            ]);
        });
    }
};
