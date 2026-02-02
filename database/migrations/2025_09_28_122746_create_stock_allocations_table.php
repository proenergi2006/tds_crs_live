<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('stock_allocations', function (Blueprint $table) {
            $table->bigIncrements('id');

            // FK ke detail PR dan ke stok
            $table->unsignedBigInteger('pr_detail_id'); // pro_pr_detail.id_prd
            $table->unsignedBigInteger('stock_id');     // stocks.id

            // jumlah yang dialokasikan dari baris stok tsb
            $table->decimal('allocated_volume', 22, 4);
            // (opsional) harga tebus yang dipakai saat alokasi
            $table->decimal('allocated_price', 22, 4)->nullable();

            // metadata
            $table->timestamps();

            // index
            $table->index('pr_detail_id');
            $table->index('stock_id');

            // jika satu PR detail bisa ambil dari banyak stock (yes),
            // maka kombinasi ini boleh berulang; tapi
            // seringnya kita ingin cegah duplikat tepat sama:
            $table->unique(['pr_detail_id','stock_id'], 'stock_allocations_uq');

            // FK (ON DELETE CASCADE agar bersih saat PR detail dihapus)
            $table->foreign('pr_detail_id', 'stock_allocations_fk_prd')
                  ->references('id_prd')->on('pro_pr_detail')
                  ->onDelete('cascade')->onUpdate('cascade');

            // Kalau stock dihapus, biasanya dilarang jika masih dipakai (RESTRICT).
            // Kalau ingin ikut terhapus, ganti ke ->onDelete('cascade')
            $table->foreign('stock_id', 'stock_allocations_fk_stock')
                  ->references('id')->on('stocks')
                  ->onDelete('restrict')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('stock_allocations', function (Blueprint $table) {
            $table->dropForeign('stock_allocations_fk_prd');
            $table->dropForeign('stock_allocations_fk_stock');
            $table->dropUnique('stock_allocations_uq');
        });
        Schema::dropIfExists('stock_allocations');
    }
};
