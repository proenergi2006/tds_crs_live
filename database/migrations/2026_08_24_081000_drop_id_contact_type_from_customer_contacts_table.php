<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Tipe kontak dihapus: kontak per-site sudah dibedakan lewat id_lcr, kontak level-company lewat position.
    public function up(): void
    {
        Schema::table('customer_contacts', function (Blueprint $table) {
            $table->dropForeign(['id_contact_type']);
            $table->dropIndex(['id_customer', 'id_contact_type']);
            $table->dropColumn('id_contact_type');

            $table->index('id_customer');
        });
    }

    public function down(): void
    {
        Schema::table('customer_contacts', function (Blueprint $table) {
            $table->dropIndex(['id_customer']);
            $table->unsignedBigInteger('id_contact_type')->nullable();
        });

        Schema::table('customer_contacts', function (Blueprint $table) {
            $table->foreign('id_contact_type')
                ->references('id_contact_type')->on('customer_contact_types')
                ->onDelete('restrict');

            $table->index(['id_customer', 'id_contact_type']);
        });
    }
};
