<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // gap lama: model/request/controller sudah pakai type_pengiriman, tapi kolomnya
        // gak pernah ke-migrate ke tabel TDS (cuma ada di penawarans_proenergi) -- guard
        // hasColumn jaga-jaga kalau di environment lain kolomnya udah ada duluan
        if (!Schema::hasColumn('penawarans', 'type_pengiriman')) {
            Schema::table('penawarans', function (Blueprint $table) {
                $table->string('type_pengiriman')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('penawarans', 'type_pengiriman')) {
            Schema::table('penawarans', function (Blueprint $table) {
                $table->dropColumn('type_pengiriman');
            });
        }
    }
};
