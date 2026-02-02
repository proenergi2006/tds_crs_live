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
        Schema::table('penawarans', function (Blueprint $table) {
            // Tambahkan setelah kolom 'metode' supaya urut
            $table->enum('type_pengiriman', ['PROJECT', 'RETAIL'])
                  ->nullable()
                  ->after('metode')
                  ->comment('Jenis pengiriman: PROJECT = FOB/CIF/DAP, RETAIL = FOT/FRANCO');
        });
    }

    public function down(): void
    {
        Schema::table('penawarans', function (Blueprint $table) {
            $table->dropColumn('type_pengiriman');
        });
    }
};
