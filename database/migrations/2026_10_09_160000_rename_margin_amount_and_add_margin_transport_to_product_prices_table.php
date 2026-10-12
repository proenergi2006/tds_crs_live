<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE product_prices RENAME COLUMN margin_amount TO margin_material_amount');
        DB::statement('ALTER TABLE product_prices ADD COLUMN margin_transport_amount numeric(18,2) NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE product_prices DROP COLUMN margin_transport_amount');
        DB::statement('ALTER TABLE product_prices RENAME COLUMN margin_material_amount TO margin_amount');
    }
};
