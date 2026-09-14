<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('po_customers', function (Blueprint $table) {
            $table->string('tipe_bayar', 20)->nullable();
            $table->integer('termin_hari')->nullable();
        });

        DB::statement("UPDATE po_customers SET tipe_bayar = 'CREDIT', termin_hari = NULLIF(substring(top_poc FROM '[0-9]+'), '')::integer");

        $unfilled = DB::table('po_customers')->whereNull('tipe_bayar')->count();

        if ($unfilled > 0) {
            throw new RuntimeException("Migration dibatalkan: {$unfilled} baris po_customers masih memiliki tipe_bayar NULL setelah backfill — kolom tidak di-set NOT NULL dengan nilai default diam-diam.");
        }

        Schema::table('po_customers', function (Blueprint $table) {
            $table->string('tipe_bayar', 20)->nullable(false)->change();
        });

        Schema::table('po_customers', function (Blueprint $table) {
            $table->dropColumn('top_poc');
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Irreversible by data — teks bebas top_poc ("30 hari" vs "30") tidak bisa direkonstruksi dari termin_hari, dan baris CBD/COD tidak punya padanan top_poc. Rollback = restore dump po_customers yang diambil sebelum migrate.');
    }
};
