<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('penawaran_ongkos', function (Blueprint $table) {
            // dari numeric(12,2) -> varchar(20)
            $table->string('volume', 20)->change();
        });
    }

    public function down(): void
    {
        Schema::table('penawaran_ongkos', function (Blueprint $table) {
            // rollback ke numeric(12,2)
            $table->decimal('volume', 12, 2)->change();
        });
    }
};
