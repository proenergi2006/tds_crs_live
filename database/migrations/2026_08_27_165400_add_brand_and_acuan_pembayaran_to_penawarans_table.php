<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('penawarans', function (Blueprint $table) {
            if (!Schema::hasColumn('penawarans', 'brand')) {
                $table->string('brand')->default('tds');
            }
            if (!Schema::hasColumn('penawarans', 'acuan_pembayaran')) {
                $table->string('acuan_pembayaran', 50)->nullable();
            }
        });

        $constraintExists = DB::selectOne(
            "SELECT 1 FROM pg_constraint WHERE conname = 'penawarans_brand_check'"
        );
        if (!$constraintExists) {
            DB::statement(
                "ALTER TABLE penawarans ADD CONSTRAINT penawarans_brand_check "
                . "CHECK (brand IN ('tds', 'proenergi'))"
            );
        }
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE penawarans DROP CONSTRAINT IF EXISTS penawarans_brand_check');

        Schema::table('penawarans', function (Blueprint $table) {
            if (Schema::hasColumn('penawarans', 'acuan_pembayaran')) {
                $table->dropColumn('acuan_pembayaran');
            }
            if (Schema::hasColumn('penawarans', 'brand')) {
                $table->dropColumn('brand');
            }
        });
    }
};
