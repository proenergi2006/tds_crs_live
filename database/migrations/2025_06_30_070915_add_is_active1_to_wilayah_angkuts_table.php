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
    // Duplikat migration _070459_ (kolom sama persis) -- guard biar gak error di history yang belum kena kolomnya duluan.
    if (!Schema::hasColumn('wilayah_angkuts', 'is_active')) {
        Schema::table('wilayah_angkuts', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('destinasi');
        });
    }
}

public function down()
{
    //
}

};
