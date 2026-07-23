<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['top_payment', 'jenis_net']);
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->integer('top_payment')->nullable();
            // Skema live sudah `varchar` (bukan `integer` seperti definisi
            // migration awal 2025_08_15_031222) -- rollback mengikuti tipe
            // live saat ini, bukan definisi migration lama yang sudah usang.
            $table->string('jenis_net')->nullable();
        });
    }
};
