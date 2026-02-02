<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUkuransTable extends Migration
{
    public function up()
    {
        Schema::create('ukurans', function (Blueprint $table) {
            $table->id('id_ukuran');
            $table->string('nama_ukuran');
            // foreign key ke tabel satuans(id_satuan)
            $table->foreignId('id_satuan')
                  ->constrained('satuans', 'id_satuan')
                  ->cascadeOnDelete();
            $table->integer('nilai');
            $table->string('jenis');
            $table->timestamp('created_time')->useCurrent();
            $table->string('created_by');
            $table->timestamp('lastupdate_time')->nullable();
            $table->string('lastupdate_by')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('ukurans');
    }
}
