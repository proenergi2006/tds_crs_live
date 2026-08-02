<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `notes` awalnya diisi Marketing bareng credit_limit_request/top_request. Ternyata
 * field ini seharusnya diisi Admin Finance saat menutup KYC, bareng credit_limit_approval/
 * top_approval, bukan catatan bebas dari Marketing. Ini rename murni supaya nama kolom
 * cocok sama isi dan pemiliknya yang baru; data lama (kalau ada) tetap terbawa.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_credit_submissions', function (Blueprint $table) {
            $table->renameColumn('notes', 'financial_review');
        });
    }

    public function down(): void
    {
        Schema::table('customer_credit_submissions', function (Blueprint $table) {
            $table->renameColumn('financial_review', 'notes');
        });
    }
};
