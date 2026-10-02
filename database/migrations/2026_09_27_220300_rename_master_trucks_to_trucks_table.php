<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $nonIntegerCapacities = DB::table('master_trucks')
            ->whereRaw('kapasitas <> floor(kapasitas)')
            ->pluck('kapasitas', 'id');

        if ($nonIntegerCapacities->isNotEmpty()) {
            throw new RuntimeException('Non-integer kapasitas values found: ' . $nonIntegerCapacities->toJson());
        }

        Schema::table('master_trucks', function (Blueprint $table) {
            $table->renameColumn('id_transportir', 'transporter_id');
        });

        Schema::table('master_trucks', function (Blueprint $table) {
            $table->renameColumn('nopol', 'license_plate');
        });

        Schema::table('master_trucks', function (Blueprint $table) {
            $table->renameColumn('jenis_truck', 'type');
        });

        Schema::table('master_trucks', function (Blueprint $table) {
            $table->renameColumn('kapasitas', 'max_capacity');
        });

        Schema::table('master_trucks', function (Blueprint $table) {
            $table->renameColumn('nama_truck', 'name');
        });

        DB::statement('ALTER TABLE master_trucks ALTER COLUMN max_capacity TYPE integer USING max_capacity::integer');

        Schema::rename('master_trucks', 'trucks');

        Schema::table('trucks', function (Blueprint $table) {
            $table->index('transporter_id');
        });
    }

    public function down(): void
    {
        Schema::table('trucks', function (Blueprint $table) {
            $table->dropIndex('trucks_transporter_id_index');
        });

        Schema::rename('trucks', 'master_trucks');

        DB::statement('ALTER TABLE master_trucks ALTER COLUMN max_capacity TYPE double precision');

        Schema::table('master_trucks', function (Blueprint $table) {
            $table->renameColumn('transporter_id', 'id_transportir');
        });

        Schema::table('master_trucks', function (Blueprint $table) {
            $table->renameColumn('license_plate', 'nopol');
        });

        Schema::table('master_trucks', function (Blueprint $table) {
            $table->renameColumn('type', 'jenis_truck');
        });

        Schema::table('master_trucks', function (Blueprint $table) {
            $table->renameColumn('max_capacity', 'kapasitas');
        });

        Schema::table('master_trucks', function (Blueprint $table) {
            $table->renameColumn('name', 'nama_truck');
        });
    }
};
