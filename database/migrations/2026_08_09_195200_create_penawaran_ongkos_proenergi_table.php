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
        if (Schema::hasTable('penawaran_ongkos_proenergi')) {
            return;
        }

        Schema::create('penawaran_ongkos_proenergi', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('penawaran_id');
            $table->unsignedBigInteger('wilayah_id');
            $table->unsignedBigInteger('transportir_id');
            $table->decimal('ongkos', 15, 2);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->unsignedBigInteger('volume_id')->nullable();
            $table->string('jenis', 10)->nullable();

            $table->foreign('penawaran_id')->references('id_penawaran')->on('penawarans_proenergi')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('transportir_id')->references('id')->on('transportirs')->onUpdate('cascade')->onDelete('restrict');
            $table->foreign('volume_id')->references('id_volume')->on('volumes')->onUpdate('cascade')->onDelete('restrict');
            $table->foreign('wilayah_id')->references('id')->on('wilayah_angkuts')->onUpdate('cascade')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penawaran_ongkos_proenergi');
    }
};
