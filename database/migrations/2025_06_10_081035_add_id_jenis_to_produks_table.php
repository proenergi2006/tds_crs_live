<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIdJenisToProduksTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('produks', function (Blueprint $table) {
            $table->unsignedBigInteger('id_jenis')->nullable()->after('id'); // atau atur posisi sesuai kebutuhan

            $table->foreign('id_jenis')
                  ->references('id_jenis')
                  ->on('jenis_produks')
                  ->onUpdate('cascade')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('produks', function (Blueprint $table) {
            $table->dropForeign(['id_jenis']);
            $table->dropColumn('id_jenis');
        });
    }
}
