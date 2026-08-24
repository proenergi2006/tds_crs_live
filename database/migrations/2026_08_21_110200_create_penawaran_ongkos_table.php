<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // tabel ini ada di DB live (dipakai aktif oleh Penawaran::ongkos() untuk OA
        // Kapal/Truck) tapi gak pernah tercatat di migration manapun -- drift sama
        // seperti produk_hargas, direkonstruksi persis dari struktur live
        if (Schema::hasTable('penawaran_ongkos')) {
            return;
        }

        Schema::create('penawaran_ongkos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('penawaran_id');
            $table->unsignedBigInteger('wilayah_id');
            $table->unsignedBigInteger('transportir_id');
            $table->decimal('ongkos', 15, 2);
            $table->timestamps();
            $table->unsignedBigInteger('volume_id')->nullable();
            $table->string('jenis', 10)->nullable();

            $table->foreign('penawaran_id')->references('id_penawaran')->on('penawarans')->onDelete('cascade');
            $table->foreign('wilayah_id')->references('id')->on('wilayah_angkuts');
            $table->foreign('transportir_id')->references('id')->on('transportirs');

            $table->index('jenis');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penawaran_ongkos');
    }
};
