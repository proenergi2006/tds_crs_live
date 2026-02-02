<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('penawaran_ongkos', function (Blueprint $table) {
            // 15 digit total, 2 digit desimal (aman untuk rupiah besar)
            $table->decimal('ongkos', 15, 2)->change();
        });
    }

    public function down(): void
    {
        Schema::table('penawaran_ongkos', function (Blueprint $table) {
            // rollback ke bigint kalau perlu
            $table->bigInteger('ongkos')->change();
        });
    }
};
