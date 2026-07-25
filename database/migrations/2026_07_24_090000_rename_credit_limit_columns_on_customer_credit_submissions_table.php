<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Task DBML-F (customer-kyc-dbml-alignment-migration.md) -- selaraskan
 * penamaan kolom credit limit di customer_credit_submissions ke pola
 * request/approval yang sudah dipakai tabel sibling customer_credit_items,
 * supaya satu domain kredit konsisten satu konvensi penamaan.
 *
 * Rename murni -- data 5 row backfill dari Task DBML-A/DBML-D ikut terbawa
 * tanpa perlu re-backfill.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_credit_submissions', function (Blueprint $table) {
            $table->renameColumn('credit_limit_diajukan', 'credit_limit_request');
            $table->renameColumn('credit_limit', 'credit_limit_approval');
        });
    }

    public function down(): void
    {
        Schema::table('customer_credit_submissions', function (Blueprint $table) {
            $table->renameColumn('credit_limit_approval', 'credit_limit');
            $table->renameColumn('credit_limit_request', 'credit_limit_diajukan');
        });
    }
};
