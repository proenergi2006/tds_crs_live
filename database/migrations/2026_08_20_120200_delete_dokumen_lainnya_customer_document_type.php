<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // dokumen_lainnya digantikan pola document_name freetext; 0 dependency data saat penghapusan.
    public function up(): void
    {
        DB::table('customer_document_types')->where('code', 'dokumen_lainnya')->delete();
    }

    public function down(): void
    {
        DB::table('customer_document_types')->insert([
            'code' => 'dokumen_lainnya',
            'name' => 'Dokumen Lainnya',
            'category' => 'onboarding',
            'is_active' => true,
            'sort_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};
