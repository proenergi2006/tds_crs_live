<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('master_kapals', function (Blueprint $table) {
            $table->dropForeign('master_kapals_id_transportir_foreign');
        });

        Schema::table('master_kapals', function (Blueprint $table) {
            $table->renameColumn('id_transportir', 'transporter_id');
        });

        Schema::table('master_kapals', function (Blueprint $table) {
            $table->renameColumn('nama_kapal', 'name');
        });

        Schema::table('master_kapals', function (Blueprint $table) {
            $table->renameColumn('kapasitas_max', 'max_capacity');
        });

        Schema::table('master_kapals', function (Blueprint $table) {
            $table->renameColumn('kelas', 'classification');
        });

        Schema::table('master_kapals', function (Blueprint $table) {
            $table->renameColumn('panjang', 'length');
        });

        Schema::table('master_kapals', function (Blueprint $table) {
            $table->renameColumn('lebar', 'width');
        });

        Schema::table('master_kapals', function (Blueprint $table) {
            $table->renameColumn('asal_kapal', 'origin');
        });

        Schema::table('master_kapals', function (Blueprint $table) {
            $table->renameColumn('tipe_kapal', 'type');
        });

        Schema::rename('master_kapals', 'vessels');

        Schema::table('vessels', function (Blueprint $table) {
            $table->index('transporter_id');
            $table->foreign('transporter_id')->references('id')->on('transporters');
        });
    }

    public function down(): void
    {
        Schema::table('vessels', function (Blueprint $table) {
            $table->dropForeign('vessels_transporter_id_foreign');
            $table->dropIndex('vessels_transporter_id_index');
        });

        Schema::rename('vessels', 'master_kapals');

        Schema::table('master_kapals', function (Blueprint $table) {
            $table->renameColumn('transporter_id', 'id_transportir');
        });

        Schema::table('master_kapals', function (Blueprint $table) {
            $table->renameColumn('name', 'nama_kapal');
        });

        Schema::table('master_kapals', function (Blueprint $table) {
            $table->renameColumn('max_capacity', 'kapasitas_max');
        });

        Schema::table('master_kapals', function (Blueprint $table) {
            $table->renameColumn('classification', 'kelas');
        });

        Schema::table('master_kapals', function (Blueprint $table) {
            $table->renameColumn('length', 'panjang');
        });

        Schema::table('master_kapals', function (Blueprint $table) {
            $table->renameColumn('width', 'lebar');
        });

        Schema::table('master_kapals', function (Blueprint $table) {
            $table->renameColumn('origin', 'asal_kapal');
        });

        Schema::table('master_kapals', function (Blueprint $table) {
            $table->renameColumn('type', 'tipe_kapal');
        });

        Schema::table('master_kapals', function (Blueprint $table) {
            $table->foreign('id_transportir')->references('id')->on('transporters')->onDelete('cascade');
        });
    }
};
