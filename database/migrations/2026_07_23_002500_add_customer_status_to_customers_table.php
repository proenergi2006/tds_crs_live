<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            // String biasa (bukan DB-level enum), di-cast ke PHP backed enum
            // App\Enums\CustomerStatus di model.
            $table->string('customer_status')->default('prospect')->after('inco_terms_other');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('customer_status');
        });
    }
};
