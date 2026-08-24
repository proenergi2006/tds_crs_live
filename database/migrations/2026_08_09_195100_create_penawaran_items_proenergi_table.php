<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Direkonstruksi dari backup dump (2026-08-20), sama seperti penawarans_proenergi -- gak pernah punya migration.
return new class extends Migration
{
    public function up(): void
    {
        // Guard: production kemungkinan sudah punya tabel ini dari pembuatan manual dulu -- no-op kalau sudah ada.
        if (Schema::hasTable('penawaran_items_proenergi')) {
            return;
        }

        Schema::create('penawaran_items_proenergi', function (Blueprint $table) {
            $table->id('id_penawaran_item');
            $table->unsignedBigInteger('id_penawaran');
            $table->unsignedBigInteger('id_produk');
            $table->decimal('volume_order', 20, 2)->default(0);
            $table->decimal('harga_tebus', 20, 2)->default(0);
            $table->decimal('jumlah_harga', 30, 2)->default(0);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->decimal('persen', 5, 2)->default(0);

            $table->foreign('id_penawaran')->references('id_penawaran')->on('penawarans_proenergi')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('id_produk')->references('id_produk')->on('produks')->onUpdate('cascade')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penawaran_items_proenergi');
    }
};
