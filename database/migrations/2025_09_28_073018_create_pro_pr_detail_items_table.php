<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pro_pr_detail_items', function (Blueprint $table) {
            $table->bigIncrements('id_prdi');
            $table->unsignedBigInteger('id_prd');                 // FK ke pro_pr_detail
            $table->unsignedBigInteger('id_penawaran_item')->nullable(); // FK ke penawaran_items (opsional, tergantung DB)
            $table->string('produk', 100)->nullable();            // nama produk
            $table->string('ukuran', 50)->nullable();             // ukuran/variant
            $table->integer('volume_item')->default(0);           // volume untuk item ini
            $table->integer('harga_item')->nullable();            // kalau mau simpan harga per item
            $table->integer('subtotal')->nullable();              // harga_item * volume_item (opsional)

            $table->index('id_prd', 'pro_prdi_idx_prd');
            $table->index('id_penawaran_item', 'pro_prdi_idx_penawaran_item');

            $table->foreign('id_prd')
                  ->references('id_prd')->on('pro_pr_detail')
                  ->onDelete('cascade')->onUpdate('cascade');
            // jika ingin enforce FK ke penawaran_items, pastikan tabel & PKnya ada:
            // $table->foreign('id_penawaran_item')
            //       ->references('id_item')->on('penawaran_items')
            //       ->onDelete('restrict')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pro_pr_detail_items');
    }
};
