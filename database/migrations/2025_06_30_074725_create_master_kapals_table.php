<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMasterKapalsTable extends Migration
{
    public function up()
    {
        Schema::create('master_kapals', function (Blueprint $table) {
            $table->id();
            $table->string('nama_kapal');
            $table->integer('kapasitas_max')->nullable();
            $table->string('kelas')->nullable();
            $table->decimal('panjang', 8, 2)->nullable();
            $table->decimal('lebar', 8, 2)->nullable();
            $table->string('asal_kapal')->nullable();
            $table->string('tipe_kapal')->nullable();
            $table->foreignId('id_transportir')->constrained('transportirs')->onDelete('cascade');
            $table->string('dokumen')->nullable();
            $table->date('masa_berlaku')->nullable();
            $table->string('lampiran')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('master_kapals');
    }
}
