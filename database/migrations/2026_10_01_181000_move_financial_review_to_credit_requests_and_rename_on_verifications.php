<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_credit_requests', function (Blueprint $table) {
            $table->text('financial_review')->nullable();
        });

        $this->seedFinancialReviewDrafts();

        Schema::table('customer_verifications', function (Blueprint $table) {
            $table->renameColumn('financial_review', 'notes');
        });

        Schema::table('customer_verifications', function (Blueprint $table) {
            $table->text('financial_review_snapshot')->nullable();
            $table->jsonb('finance_attachments')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('customer_verifications', function (Blueprint $table) {
            $table->dropColumn(['finance_attachments', 'financial_review_snapshot']);
        });

        Schema::table('customer_verifications', function (Blueprint $table) {
            $table->renameColumn('notes', 'financial_review');
        });

        Schema::table('customer_credit_requests', function (Blueprint $table) {
            $table->dropColumn('financial_review');
        });
    }

    private function seedFinancialReviewDrafts(): void
    {
        $latestPerCustomer = DB::table('customer_verifications')
            ->select('id_customer', DB::raw('MAX(id_verification) as id_verification'))
            ->whereNotNull('financial_review')
            ->whereRaw("trim(financial_review) <> ''")
            ->groupBy('id_customer');

        $drafts = DB::table('customer_verifications as cv')
            ->joinSub($latestPerCustomer, 'latest', function ($join) {
                $join->on('cv.id_customer', '=', 'latest.id_customer')
                    ->on('cv.id_verification', '=', 'latest.id_verification');
            })
            ->select('cv.id_customer', 'cv.financial_review')
            ->get();

        foreach ($drafts as $draft) {
            DB::table('customer_credit_requests')
                ->where('id_customer', $draft->id_customer)
                ->update(['financial_review' => $draft->financial_review]);
        }
    }
};
