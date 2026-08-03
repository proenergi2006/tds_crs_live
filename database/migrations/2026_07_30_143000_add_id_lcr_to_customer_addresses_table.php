<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_addresses', function (Blueprint $table) {
            // Nullable, cuma keisi buat address_type=site_address (sisanya NULL).
            // Unique index disini biar 1 site LCR maks kepasang ke 1 address --
            // aman aja, Postgres gak anggap NULL-NULL itu duplikat.
            $table->unsignedBigInteger('id_lcr')->nullable();

            $table->foreign('id_lcr')
                ->references('id_lcr')->on('customer_lcr')
                ->onDelete('set null');

            $table->unique('id_lcr');
        });
    }

    public function down(): void
    {
        Schema::table('customer_addresses', function (Blueprint $table) {
            $table->dropForeign(['id_lcr']);
            $table->dropUnique(['id_lcr']);
            $table->dropColumn('id_lcr');
        });
    }
};
