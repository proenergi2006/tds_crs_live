<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_payment', function (Blueprint $table) {
            // String biasa (bukan DB-level enum), di-cast ke PHP backed enum
            // App\Enums\CustomerPaymentTerm/CustomerPaymentTermBasis di model.
            $table->string('payment_term')->nullable()->after('payment_method_other');
            $table->unsignedInteger('payment_term_days')->nullable()->after('payment_term');
            $table->string('payment_term_basis')->nullable()->after('payment_term_days');
        });
    }

    public function down(): void
    {
        Schema::table('customer_payment', function (Blueprint $table) {
            $table->dropColumn(['payment_term', 'payment_term_days', 'payment_term_basis']);
        });
    }
};
