<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_lcr', function (Blueprint $table) {
            $table->foreign('id_customer')
                ->references('id_customer')->on('customers')
                ->onDelete('cascade');

            $table->foreign('id_wil_oa')
                ->references('id')->on('wilayah_angkuts')
                ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::table('customer_lcr', function (Blueprint $table) {
            $table->dropForeign(['id_customer']);
            $table->dropForeign(['id_wil_oa']);
        });
    }
};
