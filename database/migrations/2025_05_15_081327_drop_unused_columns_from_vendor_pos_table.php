<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropUnusedColumnsFromVendorPosTable extends Migration
{
    public function up()
    {
        Schema::table('vendor_pos', function (Blueprint $table) {
            $table->dropForeign(['id_produk']);
            $table->dropColumn(['id_produk', 'volume_po', 'harga_tebus', 'is_close', 'is_cancel', 'keterangan_cancel', 'tanggal_close', 'volume_close']);
        });
    }

    public function down()
    {
        Schema::table('vendor_pos', function (Blueprint $table) {
            // Jika perlu rollback, bisa tambahkan kembali kolom yang di-drop:
            $table->unsignedBigInteger('id_produk')->after('id_terminal');
            $table->decimal('volume_po', 22, 4)->after('tanggal_inven');
            $table->decimal('harga_tebus', 15, 2)->after('volume_po');
            $table->boolean('is_close')->default(0)->after('keterangan');
            $table->boolean('is_cancel')->default(0)->after('is_close');
            $table->text('keterangan_cancel')->nullable()->after('is_cancel');
            $table->date('tanggal_close')->nullable()->after('keterangan_cancel');
            $table->integer('volume_close')->nullable()->after('tanggal_close');

            // dan foreign key id_produk jika masih diperlukan:
            $table->foreign('id_produk')
                  ->references('id_produk')
                  ->on('produks')
                  ->onDelete('restrict');
        });
    }
}
