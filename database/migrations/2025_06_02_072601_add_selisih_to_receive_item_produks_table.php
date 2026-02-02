<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSelisihToReceiveItemProduksTable extends Migration
{
    public function up()
    {
        Schema::table('receive_item_produks', function (Blueprint $table) {
            // Tambahkan kolom selisih (decimal). 
            // Kita gunakan precision dan scale yang sama dengan volume_bl / volume_terima (mis. 15,2).
            $table->decimal('selisih', 15, 2)->default(0)->after('volume_terima');
        });
    }

    public function down()
    {
        Schema::table('receive_item_produks', function (Blueprint $table) {
            $table->dropColumn('selisih');
        });
    }
}
