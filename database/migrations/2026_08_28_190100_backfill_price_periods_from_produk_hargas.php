<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $periods = DB::table('produk_hargas')
            ->select('periode_awal', 'periode_akhir')
            ->distinct()
            ->orderBy('periode_awal')
            ->get();

        foreach ($periods as $period) {
            $createdAt = DB::table('produk_hargas')
                ->where('periode_awal', $period->periode_awal)
                ->where('periode_akhir', $period->periode_akhir)
                ->min('created_time');

            DB::table('price_periods')->insert([
                'start_date' => $period->periode_awal,
                'end_date'   => $period->periode_akhir,
                'created_at' => $createdAt ?? now(),
                'updated_at' => $createdAt ?? now(),
                'created_by' => 'system-migration',
            ]);
        }
    }

    public function down(): void
    {
        DB::table('price_periods')->where('created_by', 'system-migration')->delete();
    }
};
