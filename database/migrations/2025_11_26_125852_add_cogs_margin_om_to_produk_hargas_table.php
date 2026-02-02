<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('produk_hargas', function (Blueprint $table) {
            $table->decimal('harga_cogs', 18, 2)->nullable()->after('harga_bm');
            $table->decimal('harga_margin', 18, 2)->nullable()->after('harga_cogs');
            $table->decimal('harga_om', 18, 2)->nullable()->after('harga_margin');
        });
    }
    
    public function down()
    {
        Schema::table('produk_hargas', function (Blueprint $table) {
            $table->dropColumn(['harga_cogs', 'harga_margin', 'harga_om']);
        });
    }
    
};
