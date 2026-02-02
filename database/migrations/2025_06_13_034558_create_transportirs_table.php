<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransportirsTable extends Migration
{
    public function up(): void
    {
        Schema::create('transportirs', function (Blueprint $table) {
            $table->id();

            $table->string('nama_perusahaan');
            $table->string('singkatan', 20)->nullable();
            $table->string('kepemilikan')->nullable(); // PT, CV, Pribadi, dll
            $table->string('terms')->nullable();
            $table->unsignedBigInteger('id_cabang')->nullable(); // relasi ke cabangs
            $table->string('alamat')->nullable();
            $table->string('telpon')->nullable();
            $table->string('fax')->nullable();
            $table->string('angkutan_kirim')->nullable(); // misal: darat, laut, dll
            $table->boolean('is_active')->default(true);
            $table->string('email')->nullable();
            $table->string('nomor_hp')->nullable();
            $table->string('perizinan')->nullable(); // misal: SIUP, NIB, dll
            $table->date('masa_berlaku')->nullable();
            $table->string('lampiran')->nullable(); // file path PDF/dokumen

            $table->timestamps(); // created_at & updated_at
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            // Foreign key constraint jika diperlukan
            // $table->foreign('id_cabang')->references('id')->on('cabangs')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transportirs');
    }
}
