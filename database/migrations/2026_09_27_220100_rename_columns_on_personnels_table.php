<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personnels', function (Blueprint $table) {
            $table->dropForeign('personnels_id_transportir_foreign');
        });

        Schema::table('personnels', function (Blueprint $table) {
            $table->renameColumn('id_transportir', 'transporter_id');
        });

        Schema::table('personnels', function (Blueprint $table) {
            $table->renameColumn('nama', 'name');
        });

        Schema::table('personnels', function (Blueprint $table) {
            $table->index('transporter_id');
            $table->foreign('transporter_id')->references('id')->on('transporters');
        });
    }

    public function down(): void
    {
        Schema::table('personnels', function (Blueprint $table) {
            $table->dropForeign('personnels_transporter_id_foreign');
            $table->dropIndex('personnels_transporter_id_index');
        });

        Schema::table('personnels', function (Blueprint $table) {
            $table->renameColumn('transporter_id', 'id_transportir');
        });

        Schema::table('personnels', function (Blueprint $table) {
            $table->renameColumn('name', 'nama');
        });

        Schema::table('personnels', function (Blueprint $table) {
            $table->foreign('id_transportir')->references('id')->on('transporters')->onDelete('cascade');
        });
    }
};
