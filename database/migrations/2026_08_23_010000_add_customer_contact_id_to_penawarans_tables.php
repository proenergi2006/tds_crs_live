<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Nullable: baris pre-migrasi tidak punya kontak tujuan; keharusan isi ditegakkan di app-layer.
    public function up(): void
    {
        Schema::table('penawarans', function (Blueprint $table) {
            $table->unsignedBigInteger('customer_contact_id')->nullable();

            $table->foreign('customer_contact_id')
                ->references('id_contact')->on('customer_contacts')
                ->onDelete('restrict');
        });

        Schema::table('penawarans_proenergi', function (Blueprint $table) {
            $table->unsignedBigInteger('customer_contact_id')->nullable();

            $table->foreign('customer_contact_id')
                ->references('id_contact')->on('customer_contacts')
                ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::table('penawarans', function (Blueprint $table) {
            $table->dropForeign(['customer_contact_id']);
            $table->dropColumn('customer_contact_id');
        });

        Schema::table('penawarans_proenergi', function (Blueprint $table) {
            $table->dropForeign(['customer_contact_id']);
            $table->dropColumn('customer_contact_id');
        });
    }
};
