<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModifyProduksTableAddIsActiveDropStock extends Migration
{
    public function up()
    {
        Schema::table('produks', function (Blueprint $table) {
            // hapus kolom stock
            if (Schema::hasColumn('produks', 'stock')) {
                $table->dropColumn('stock');
            }
            // tambahkan kolom is_active
            $table->boolean('is_active')->default(true)->after('id_ukuran');
        });
    }

    public function down()
    {
        Schema::table('produks', function (Blueprint $table) {
            // kalau rollback, kembalikan stock dan drop is_active
            $table->integer('stock')->default(0)->after('id_ukuran');
            $table->dropColumn('is_active');
        });
    }
}
