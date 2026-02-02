<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePenawaransTable extends Migration
{
    public function up()
    {
        Schema::create('penawarans', function (Blueprint $table) {
            $table->bigIncrements('id_penawaran');

            // FK ke tabel customers
            $table->unsignedBigInteger('id_customer');
            // FK ke tabel cabangs
            $table->unsignedBigInteger('id_cabang');

            $table->string('nomor_penawaran')->unique();
            $table->date('masa_berlaku');
            $table->date('sampai_dengan');

            // Total‐total
            $table->decimal('subtotal', 20, 2)->default(0);
            $table->decimal('ppn11', 20, 2)->default(0);
            $table->decimal('total',   20, 2)->default(0);

            // Field‐field lain
            $table->string('kepada')->nullable();
            $table->string('jabatan')->nullable();
            $table->string('telepon')->nullable();
            $table->string('nama')->nullable();
            $table->text('alamat')->nullable();
            $table->string('fax')->nullable();
            $table->string('tipe_pembayaran')->nullable();
            $table->string('order_method')->nullable();
            $table->decimal('toleransi_penyusutan', 8, 2)->nullable();
            $table->string('lokasi_pengiriman')->nullable();
            $table->string('metode')->nullable();
            $table->decimal('refund', 20, 2)->nullable();
            $table->decimal('other_cost', 20, 2)->nullable();
            $table->string('perhitungan')->nullable();
            $table->text('keterangan')->nullable();
            $table->text('catatan')->nullable();
            $table->text('syarat_ketentuan')->nullable();

            // Audit fields
            $table->timestamp('created_at')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->string('updated_by')->nullable();

            // Foreign key constraints
            $table->foreign('id_customer')
                  ->references('id_customer')->on('customers')
                  ->onDelete('restrict')
                  ->onUpdate('cascade');

            $table->foreign('id_cabang')
                  ->references('id_cabang')->on('cabangs')
                  ->onDelete('restrict')
                  ->onUpdate('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('penawarans');
    }
}
