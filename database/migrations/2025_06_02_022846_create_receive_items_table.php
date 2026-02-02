<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReceiveItemsTable extends Migration
{
    public function up()
    {
        Schema::create('receive_items', function (Blueprint $table) {
            $table->id(); // id_receive
            $table->unsignedBigInteger('po_id'); // FK ke vendor_pos.id_po
            $table->date('received_at');
            $table->string('nama_pic');
            $table->string('file_path')->nullable();
            $table->timestamps();

            // FK constraint
            $table->foreign('po_id')
                  ->references('id_po')->on('vendor_pos')
                  ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('receive_items');
    }
}
