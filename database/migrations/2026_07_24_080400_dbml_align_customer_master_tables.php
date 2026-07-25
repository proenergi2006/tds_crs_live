<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Task DBML-E (customer-kyc-dbml-alignment-migration.md) -- selaraskan
 * tabel master/kecil (customer_addresses, customer_contact_types,
 * customer_contacts, customer_status_history, customer_document_types) ke
 * DBML artifact `21a36fda` (§12). Murni length alignment, tanpa backfill --
 * semua tabel kosong atau nyaris kosong.
 *
 * `customer_documents` SENGAJA TIDAK disentuh (file_path tetap 255, nama
 * kolom file_name tetap dipakai) -- keputusan Engineer, DBML yang perlu
 * dikoreksi di titik ini, bukan kode.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_addresses', function (Blueprint $table) {
            $table->string('address_type', 50)->change();
            $table->string('postal_code', 20)->nullable()->change();
        });

        Schema::table('customer_contact_types', function (Blueprint $table) {
            $table->string('code', 50)->change();
            $table->string('name', 100)->change();
        });

        Schema::table('customer_contacts', function (Blueprint $table) {
            $table->string('position', 150)->nullable()->change();
            $table->string('phone', 50)->nullable()->change();
            $table->string('mobile', 50)->nullable()->change();
        });

        Schema::table('customer_status_history', function (Blueprint $table) {
            $table->string('status', 20)->change();
            $table->string('trigger_source', 10)->change();
        });

        Schema::table('customer_document_types', function (Blueprint $table) {
            $table->string('code', 50)->change();
            $table->string('name', 150)->change();
        });
    }

    public function down(): void
    {
        Schema::table('customer_document_types', function (Blueprint $table) {
            $table->string('code', 255)->change();
            $table->string('name', 255)->change();
        });

        Schema::table('customer_status_history', function (Blueprint $table) {
            $table->string('status', 255)->change();
            $table->string('trigger_source', 255)->change();
        });

        Schema::table('customer_contacts', function (Blueprint $table) {
            $table->string('position', 255)->nullable()->change();
            $table->string('phone', 255)->nullable()->change();
            $table->string('mobile', 255)->nullable()->change();
        });

        Schema::table('customer_contact_types', function (Blueprint $table) {
            $table->string('code', 255)->change();
            $table->string('name', 255)->change();
        });

        Schema::table('customer_addresses', function (Blueprint $table) {
            $table->string('address_type', 255)->change();
            $table->string('postal_code', 255)->nullable()->change();
        });
    }
};
