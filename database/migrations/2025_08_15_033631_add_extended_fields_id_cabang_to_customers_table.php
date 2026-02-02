<?php
// database/migrations/2025_08_15_010000_add_id_cabang_to_customers_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            // kolom boleh NULL (kalau belum ditetapkan cabangnya)
            // gunakan foreignId manual agar refer ke kolom PK non-standar "id_cabang"
            $table->integer('id_cabang')->nullable()->after('id_kabupaten');

            // FK → cabangs.id_cabang
            $table->foreign('id_cabang')
                  ->references('id_cabang')
                  ->on('cabangs')
                  ->cascadeOnUpdate()
                  ->nullOnDelete();   // jika cabang dihapus → set NULL
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['id_cabang']);
            $table->dropColumn('id_cabang');
        });
    }
};
