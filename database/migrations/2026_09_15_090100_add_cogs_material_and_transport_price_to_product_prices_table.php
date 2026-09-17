<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_prices', function (Blueprint $table) {
            $table->decimal('cogs_material_price', 12, 2)->nullable()->after('cogs_price');
            $table->decimal('cogs_transport_price', 12, 2)->nullable()->after('cogs_material_price');
        });
    }

    public function down(): void
    {
        Schema::table('product_prices', function (Blueprint $table) {
            $table->dropColumn(['cogs_material_price', 'cogs_transport_price']);
        });
    }
};
