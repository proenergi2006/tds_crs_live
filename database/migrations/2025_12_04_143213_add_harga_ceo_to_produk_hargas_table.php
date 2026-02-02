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
        Schema::table('produk_hargas', function (Blueprint $table) {
            $table->decimal('harga_ceo', 15, 2)->nullable()->after('harga_om');
        });
    }
    
    public function down(): void
    {
        Schema::table('produk_hargas', function (Blueprint $table) {
            $table->dropColumn('harga_ceo');
        });
    }
};
