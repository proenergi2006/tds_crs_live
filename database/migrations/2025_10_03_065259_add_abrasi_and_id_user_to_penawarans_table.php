<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('penawarans', function (Blueprint $table) {
            // abrasi: persen, boleh desimal. Biar aman, nullable dulu
            $table->decimal('abrasi', 5, 2)->nullable()->after('toleransi_penyusutan');

            // relasi user_id -> users.id
            $table->foreignId('user_id')
                  ->nullable()                 // nullable dulu agar nggak nabrak data lama
                  ->constrained('users')       // ref 'users.id'
                  ->cascadeOnUpdate()
                  ->nullOnDelete()             // kalau user dihapus, set null
                  ->after('abrasi');

            // (opsional) index untuk query cepat
            $table->index('user_id');
        });
    }

    public function down(): void {
        Schema::table('penawarans', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropConstrainedForeignId('user_id'); // drop FK + kolom
            $table->dropColumn('abrasi');
        });
    }
};
