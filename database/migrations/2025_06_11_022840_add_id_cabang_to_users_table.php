<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('id_cabang')->nullable()->after('email'); // atau sesuaikan posisinya

            // Jika ada tabel cabangs dan kamu ingin foreign key:
            $table->foreign('id_cabang')
                  ->references('id_cabang')
                  ->on('cabangs')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['id_cabang']);
            $table->dropColumn('id_cabang');
        });
    }
};
