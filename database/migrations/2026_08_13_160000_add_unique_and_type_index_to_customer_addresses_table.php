<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_addresses', function (Blueprint $table) {
            $table->index('address_type', 'customer_addresses_address_type_index');
        });

        DB::statement(<<<SQL
            CREATE UNIQUE INDEX customer_addresses_unique_type_per_customer
            ON customer_addresses (id_customer, address_type)
            WHERE address_type <> 'site_address'
        SQL);
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS customer_addresses_unique_type_per_customer');

        Schema::table('customer_addresses', function (Blueprint $table) {
            $table->dropIndex('customer_addresses_address_type_index');
        });
    }
};
