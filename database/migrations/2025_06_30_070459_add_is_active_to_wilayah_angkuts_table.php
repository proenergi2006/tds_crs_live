<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::table('wilayah_angkuts', function (Blueprint $table) {
        $table->boolean('is_active')->default(true)->after('destinasi');
    });
}

public function down()
{
    Schema::table('wilayah_angkuts', function (Blueprint $table) {
        $table->dropColumn('is_active');
    });
}
};
