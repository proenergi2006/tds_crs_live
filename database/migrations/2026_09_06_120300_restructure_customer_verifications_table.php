<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_verifications', function (Blueprint $table) {
            $table->string('status', 20)->nullable();
            $table->boolean('is_scheduled')->default(false);
            $table->timestamp('submitted_at')->nullable();
            $table->unsignedBigInteger('submitted_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->text('reject_note')->nullable();
            $table->unsignedBigInteger('requested_limit_snapshot')->nullable();
            $table->integer('requested_top_snapshot')->nullable();
            $table->unsignedBigInteger('approved_limit')->nullable();
            $table->integer('approved_top')->nullable();
            $table->text('financial_review')->nullable();
            $table->timestamps();
        });

        $unmapped = DB::table('customer_verifications')
            ->whereNotIn('kyc_status', ['draft', 'forwarded'])
            ->count();

        if ($unmapped > 0) {
            throw new RuntimeException("Migration dibatalkan: {$unmapped} row customer_verifications memiliki kyc_status di luar {draft, forwarded} — pemetaan status hanya mendukung dua nilai itu.");
        }

        $missingSource = DB::table('customer_verifications')
            ->where('kyc_status', 'forwarded')
            ->whereNull('finance_processed_at')
            ->count();

        if ($missingSource > 0) {
            throw new RuntimeException("Migration dibatalkan: {$missingSource} row 'forwarded' memiliki finance_processed_at NULL — itu satu-satunya sumber backfill untuk submitted_at, created_at, updated_at, dan reviewed_at.");
        }

        DB::table('customer_verifications')
            ->where('kyc_status', 'forwarded')
            ->update(['status' => 'approved']);

        DB::statement(
            'UPDATE customer_verifications
             SET reviewed_at = finance_processed_at,
                 submitted_at = finance_processed_at,
                 created_at = finance_processed_at,
                 updated_at = finance_processed_at
             WHERE kyc_status = ?',
            ['forwarded']
        );

        DB::statement(
            'UPDATE customer_verifications cv
             SET approved_limit           = ccs.credit_limit_approval,
                 approved_top             = ccs.top_approval,
                 financial_review         = ccs.financial_review,
                 requested_limit_snapshot = ccs.credit_limit_request,
                 requested_top_snapshot   = ccs.top_request
             FROM customer_credit_submissions ccs
             WHERE ccs.id_customer = cv.id_customer AND cv.kyc_status = ?',
            ['forwarded']
        );

        $draftApprovals = DB::table('document_approvals as da')
            ->join('customer_verifications as cv', 'cv.id_verification', '=', 'da.approvable_id')
            ->where('da.approvable_type', 'like', '%CustomerVerification%')
            ->where('cv.kyc_status', 'draft')
            ->count();

        if ($draftApprovals > 0) {
            throw new RuntimeException("Migration dibatalkan: {$draftApprovals} document_approvals menunjuk row kyc_status='draft' — row draft diasumsikan yatim sebelum dihapus.");
        }

        DB::table('customer_verifications')
            ->where('kyc_status', 'draft')
            ->delete();

        $nullStatus = DB::table('customer_verifications')
            ->whereNull('status')
            ->count();

        if ($nullStatus > 0) {
            throw new RuntimeException("Migration dibatalkan: {$nullStatus} row memiliki status NULL sebelum SET NOT NULL — nilai tidak diisi default diam-diam.");
        }

        Schema::table('customer_verifications', function (Blueprint $table) {
            $table->string('status', 20)->nullable(false)->change();
        });

        Schema::table('customer_verifications', function (Blueprint $table) {
            $table->foreign('submitted_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('reviewed_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('customer_verifications', function (Blueprint $table) {
            $table->dropColumn([
                'kyc_status',
                'verification_token',
                'expired_at',
                'is_submitted',
                'is_forwarded',
                'is_active',
                'data_type',
                'finance_data_kyc',
                'finance_data',
                'finance_summary',
                'finance_result',
                'finance_processed_at',
                'finance_pic',
                'logistics_data',
                'logistics_summary',
                'logistics_result',
                'logistics_processed_at',
                'logistics_pic',
            ]);
        });

        Schema::table('customer_verifications', function (Blueprint $table) {
            $table->index(['id_customer', 'status']);
        });
    }

    public function down(): void
    {
        // Rollback data-lossy: isi finance_*/logistics_* tak bisa direkonstruksi, jadi down() menolak jalan alih-alih memberi ilusi reversible.
        throw new RuntimeException('Irreversible by data — kolom finance_*/logistics_* sudah hilang isinya. Restore dari dump Fase 0 (lihat Task 1 plan 2026_09_06_120000).');
    }
};
