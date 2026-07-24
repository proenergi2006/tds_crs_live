<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('customer_payment', function (Blueprint $table) {
            $table->dropColumn(['telp_billing', 'fax_billing']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_payment', function (Blueprint $table) {
            $table->string('telp_billing', 20);
            $table->string('fax_billing', 20);
        });
    }
};
