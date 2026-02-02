<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCatatanAndFleetToTransportirsTable extends Migration
{
    public function up(): void
    {
        Schema::table('transportirs', function (Blueprint $table) {
            $table->text('catatan')->nullable()->after('lampiran');
            $table->boolean('fleet')->default(false)->after('catatan');
        });
    }

    public function down(): void
    {
        Schema::table('transportirs', function (Blueprint $table) {
            $table->dropColumn(['catatan', 'fleet']);
        });
    }
}
