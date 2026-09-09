<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_credit_submissions', function (Blueprint $table) {
            $table->dropIndex('customer_credit_submissions_id_customer_submission_type_index');
            $table->dropForeign(['submitted_by']);
            $table->dropColumn([
                'submission_type',
                'credit_limit_approval',
                'top_approval',
                'financial_review',
                'submitted_by',
                'submitted_at',
            ]);
        });

        Schema::table('customer_credit_submissions', function (Blueprint $table) {
            $table->renameColumn('id_submission', 'id_request');
        });

        Schema::table('customer_credit_submissions', function (Blueprint $table) {
            $table->renameColumn('credit_limit_request', 'requested_limit');
        });

        Schema::table('customer_credit_submissions', function (Blueprint $table) {
            $table->renameColumn('top_request', 'requested_top');
        });

        Schema::table('customer_credit_submissions', function (Blueprint $table) {
            $table->unsignedBigInteger('requested_limit')->nullable()->default(null)->change();
        });

        Schema::rename('customer_credit_submissions', 'customer_credit_requests');

        Schema::table('customer_credit_requests', function (Blueprint $table) {
            $table->unique('id_customer');
        });
    }

    public function down(): void
    {
        // Rollback data-lossy: kolom keputusan Admin Finance sudah pindah ke customer_verifications lalu di-drop, down() menolak jalan alih-alih memberi ilusi reversible.
        throw new RuntimeException('Irreversible by data — credit_limit_approval/top_approval/financial_review sudah dipindah dan di-drop di Fase 3/4. Restore dari dump Fase 0 (lihat Task 1 plan 2026_09_06_120000).');
    }
};
