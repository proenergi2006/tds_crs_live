<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddVerifikasiFieldsToPenawaransTable extends Migration
{
    public function up()
    {
        Schema::table('penawarans', function (Blueprint $table) {
            $table->string('bm_result')->nullable()->after('approved_by');
            $table->timestamp('bm_tanggal')->nullable()->after('bm_result');
            $table->string('om_result')->nullable()->after('bm_tanggal');
            $table->timestamp('om_tanggal')->nullable()->after('om_result');
        });
    }

    public function down()
    {
        Schema::table('penawarans', function (Blueprint $table) {
            $table->dropColumn(['bm_result', 'bm_tanggal', 'om_result', 'om_tanggal']);
        });
    }
}
