<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * legal_result/finance_result/logistics_result (checkpoint 3-approval lama)
 * digantikan kyc_status: draft -> forwarded -> closed. Backfill: row lama
 * dengan is_forwarded=true di-set kyc_status='forwarded' (best-effort, tidak
 * bisa dibedakan mana yang harusnya 'closed' dari model checkpoint lama).
 *
 * Limit kredit itu agregat, bukan per-produk -- top_payment (warisan
 * customers.top_payment lama, tidak pernah didesain jadi pasangan
 * request/approval) di-rename top_request + kolom baru top_approval,
 * konsisten dengan pola credit_limit_request/approval.
 *
 * financial_review_notes tidak pernah diisi endpoint manapun dan tidak
 * dianggap perlu secara khusus sebagai field terpisah dari catatan Marketing
 * -- di-rename langsung jadi notes (bukan kolom baru, bukan
 * reuse-dengan-nama-beda).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_verifications', function (Blueprint $table) {
            $table->string('kyc_status', 20)->default('draft')->after('is_forwarded');
        });

        DB::table('customer_verifications')
            ->where('is_forwarded', true)
            ->update(['kyc_status' => 'forwarded']);

        Schema::table('customer_credit_submissions', function (Blueprint $table) {
            $table->renameColumn('top_payment', 'top_request');
        });

        Schema::table('customer_credit_submissions', function (Blueprint $table) {
            $table->unsignedInteger('top_approval')->nullable()->after('top_request');
        });

        Schema::table('customer_credit_submissions', function (Blueprint $table) {
            $table->renameColumn('financial_review_notes', 'notes');
        });
    }

    public function down(): void
    {
        Schema::table('customer_credit_submissions', function (Blueprint $table) {
            $table->renameColumn('notes', 'financial_review_notes');
        });

        Schema::table('customer_credit_submissions', function (Blueprint $table) {
            $table->dropColumn('top_approval');
        });

        Schema::table('customer_credit_submissions', function (Blueprint $table) {
            $table->renameColumn('top_request', 'top_payment');
        });

        Schema::table('customer_verifications', function (Blueprint $table) {
            $table->dropColumn('kyc_status');
        });
    }
};
