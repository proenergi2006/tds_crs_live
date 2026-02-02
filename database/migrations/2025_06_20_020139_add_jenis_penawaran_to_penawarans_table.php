<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('penawarans', function (Blueprint $table) {
            $table->string('jenis_penawaran', 50)
                  ->nullable()
                  ->after('nomor_penawaran');
                  // taruh setelah nomor_penawaran biar logis
        });
    }

    public function down(): void
    {
        Schema::table('penawarans', function (Blueprint $table) {
            $table->dropColumn('jenis_penawaran');
        });
    }
};
