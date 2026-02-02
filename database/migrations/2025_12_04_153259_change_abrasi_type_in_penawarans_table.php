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
            // Ubah tipe kolom 'abrasi' menjadi varchar(100)
            $table->string('abrasi', 100)
                  ->nullable()
                  ->change();
        });
    }

    public function down(): void
    {
        Schema::table('penawarans', function (Blueprint $table) {
            // Kembalikan ke tipe semula (misalnya decimal atau float)
            $table->decimal('abrasi', 8, 2)
                  ->nullable()
                  ->change();
        });
    }
};
