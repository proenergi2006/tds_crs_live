<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE customer_lcr ALTER COLUMN storage_capacity TYPE VARCHAR(100) USING storage_capacity::varchar(100)");
    }

    public function down(): void
    {
        // Teks bebas ("3500 ton") tidak bisa di-cast langsung ke numeric; ambil angka di awal string, sisanya NULL.
        DB::statement("ALTER TABLE customer_lcr ALTER COLUMN storage_capacity TYPE NUMERIC(15,2) USING substring(storage_capacity from '^\s*(\d+(?:\.\d+)?)')::numeric(15,2)");
    }
};
