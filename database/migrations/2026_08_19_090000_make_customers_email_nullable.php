<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `customers.email` sudah dianggap opsional oleh validasi aplikasi
 * (StoreCustomerRequest/UpdateCustomerRequest: nullable|email), tapi kolom
 * di database masih NOT NULL sejak awal dibuat. Mismatch ini membuat
 * INSERT/UPDATE dengan email kosong lolos validasi tapi gagal di database
 * (NOT NULL violation). Kolom dilonggarkan jadi nullable agar selaras
 * dengan validasi yang sudah berjalan. `unique()` index tidak disentuh —
 * Postgres mengizinkan banyak NULL dalam unique index.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('email')->nullable(false)->change();
        });
    }
};
