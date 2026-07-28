<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn([
                'is_verified',
                'status_customer',
                'prospect_customer_date',
                'prospect_evaluated',
                'fix_customer_since',
                'fix_customer_redate',
                'print_product',
                'ajukan',
                'id_verification',
            ]);
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->renameColumn('tipe_bisnis', 'business_type');
            $table->renameColumn('ownership', 'ownership_type');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->renameColumn('business_type', 'tipe_bisnis');
            $table->renameColumn('ownership_type', 'ownership');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->tinyInteger('is_verified')->default(0);
            $table->tinyInteger('status_customer')->default(1);
            $table->date('prospect_customer_date')->nullable();
            $table->tinyInteger('prospect_evaluated')->default(0);
            $table->date('fix_customer_since')->nullable();
            $table->date('fix_customer_redate')->nullable();
            $table->text('print_product')->nullable();
            $table->tinyInteger('ajukan')->default(0);
            $table->integer('id_verification')->nullable();
        });
    }
};
