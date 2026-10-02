<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $invalidTransportCapability = DB::table('transportirs')
            ->whereNotNull('angkutan_kirim')
            ->whereNotIn('angkutan_kirim', ['Kapal', 'Truck', 'Truck & Kapal'])
            ->pluck('angkutan_kirim', 'id');

        if ($invalidTransportCapability->isNotEmpty()) {
            throw new RuntimeException('Unmapped angkutan_kirim values found: ' . $invalidTransportCapability->toJson());
        }

        $invalidOwnership = DB::table('transportirs')
            ->whereNotNull('kepemilikan')
            ->whereNotIn('kepemilikan', ['Milik Sendiri', 'Thirdparty'])
            ->pluck('kepemilikan', 'id');

        if ($invalidOwnership->isNotEmpty()) {
            throw new RuntimeException('Unmapped kepemilikan values found: ' . $invalidOwnership->toJson());
        }

        Schema::table('transportirs', function (Blueprint $table) {
            $table->dropColumn('fleet');
        });

        Schema::table('transportirs', function (Blueprint $table) {
            $table->renameColumn('nama_perusahaan', 'company_name');
        });

        Schema::table('transportirs', function (Blueprint $table) {
            $table->renameColumn('singkatan', 'short_name');
        });

        Schema::table('transportirs', function (Blueprint $table) {
            $table->renameColumn('kepemilikan', 'ownership');
        });

        Schema::table('transportirs', function (Blueprint $table) {
            $table->renameColumn('alamat', 'address');
        });

        Schema::table('transportirs', function (Blueprint $table) {
            $table->renameColumn('telpon', 'phone');
        });

        Schema::table('transportirs', function (Blueprint $table) {
            $table->renameColumn('angkutan_kirim', 'transport_capability');
        });

        Schema::table('transportirs', function (Blueprint $table) {
            $table->renameColumn('nomor_hp', 'mobile_phone');
        });

        Schema::table('transportirs', function (Blueprint $table) {
            $table->renameColumn('catatan', 'note');
        });

        DB::table('transportirs')->where('transport_capability', 'Kapal')->update(['transport_capability' => 'VESSEL']);
        DB::table('transportirs')->where('transport_capability', 'Truck')->update(['transport_capability' => 'TRUCK']);
        DB::table('transportirs')->where('transport_capability', 'Truck & Kapal')->update(['transport_capability' => 'VESSEL_TRUCK']);

        DB::table('transportirs')->where('ownership', 'Milik Sendiri')->update(['ownership' => 'OWN']);
        DB::table('transportirs')->where('ownership', 'Thirdparty')->update(['ownership' => 'THIRDPARTY']);

        Schema::table('transportirs', function (Blueprint $table) {
            $table->string('ownership', 20)->nullable()->change();
            $table->string('transport_capability', 20)->nullable()->change();
        });

        Schema::rename('transportirs', 'transporters');
    }

    public function down(): void
    {
        Schema::rename('transporters', 'transportirs');

        Schema::table('transportirs', function (Blueprint $table) {
            $table->string('ownership', 255)->nullable()->change();
            $table->string('transport_capability', 255)->nullable()->change();
        });

        DB::table('transportirs')->where('transport_capability', 'VESSEL')->update(['transport_capability' => 'Kapal']);
        DB::table('transportirs')->where('transport_capability', 'TRUCK')->update(['transport_capability' => 'Truck']);
        DB::table('transportirs')->where('transport_capability', 'VESSEL_TRUCK')->update(['transport_capability' => 'Truck & Kapal']);

        DB::table('transportirs')->where('ownership', 'OWN')->update(['ownership' => 'Milik Sendiri']);
        DB::table('transportirs')->where('ownership', 'THIRDPARTY')->update(['ownership' => 'Thirdparty']);

        Schema::table('transportirs', function (Blueprint $table) {
            $table->renameColumn('company_name', 'nama_perusahaan');
        });

        Schema::table('transportirs', function (Blueprint $table) {
            $table->renameColumn('short_name', 'singkatan');
        });

        Schema::table('transportirs', function (Blueprint $table) {
            $table->renameColumn('ownership', 'kepemilikan');
        });

        Schema::table('transportirs', function (Blueprint $table) {
            $table->renameColumn('address', 'alamat');
        });

        Schema::table('transportirs', function (Blueprint $table) {
            $table->renameColumn('phone', 'telpon');
        });

        Schema::table('transportirs', function (Blueprint $table) {
            $table->renameColumn('transport_capability', 'angkutan_kirim');
        });

        Schema::table('transportirs', function (Blueprint $table) {
            $table->renameColumn('mobile_phone', 'nomor_hp');
        });

        Schema::table('transportirs', function (Blueprint $table) {
            $table->renameColumn('note', 'catatan');
        });

        Schema::table('transportirs', function (Blueprint $table) {
            $table->boolean('fleet')->default(false);
        });
    }
};
