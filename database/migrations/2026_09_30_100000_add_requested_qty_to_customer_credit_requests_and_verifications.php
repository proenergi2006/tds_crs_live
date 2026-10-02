<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_credit_requests', function (Blueprint $table) {
            $table->decimal('requested_qty', 15, 2)->nullable();
            $table->string('product_category', 20)->nullable();
        });

        Schema::table('customer_verifications', function (Blueprint $table) {
            $table->decimal('requested_qty_snapshot', 15, 2)->nullable();
            $table->string('product_category_snapshot', 20)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('customer_credit_requests', function (Blueprint $table) {
            $table->dropColumn(['requested_qty', 'product_category']);
        });

        Schema::table('customer_verifications', function (Blueprint $table) {
            $table->dropColumn(['requested_qty_snapshot', 'product_category_snapshot']);
        });
    }
};
