<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Nullable, cuma keisi buat dokumen foto LCR (id_document_type = lcr_*), sisanya NULL.
    // Index biasa aja (bukan unique kayak customer_addresses.id_lcr) krn 1 site LCR
    // bisa punya banyak foto per kategori. metadata masih NULL, disiapin buat field fleksibel ke depan.
    public function up(): void
    {
        Schema::table('customer_documents', function (Blueprint $table) {
            $table->unsignedBigInteger('id_lcr')->nullable();
            $table->json('metadata')->nullable();

            $table->foreign('id_lcr')
                ->references('id_lcr')->on('customer_lcr')
                ->onDelete('set null');

            $table->index('id_lcr');
        });
    }

    public function down(): void
    {
        Schema::table('customer_documents', function (Blueprint $table) {
            $table->dropForeign(['id_lcr']);
            $table->dropIndex(['id_lcr']);
            $table->dropColumn(['id_lcr', 'metadata']);
        });
    }
};
