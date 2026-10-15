<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mews\Purifier\Facades\Purifier;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_verifications', function (Blueprint $table) {
            $table->renameColumn('notes', 'financial_review');
        });

        $this->mergeSnapshotIntoFinancialReview();

        Schema::table('customer_verifications', function (Blueprint $table) {
            $table->dropColumn('financial_review_snapshot');
        });

        Schema::table('customer_credit_requests', function (Blueprint $table) {
            $table->dropColumn('financial_review');
        });
    }

    public function down(): void
    {
        Schema::table('customer_credit_requests', function (Blueprint $table) {
            $table->text('financial_review')->nullable();
        });

        Schema::table('customer_verifications', function (Blueprint $table) {
            $table->text('financial_review_snapshot')->nullable();
        });

        Schema::table('customer_verifications', function (Blueprint $table) {
            $table->renameColumn('financial_review', 'notes');
        });
    }

    private function mergeSnapshotIntoFinancialReview(): void
    {
        DB::table('customer_verifications')
            ->whereNotNull('financial_review_snapshot')
            ->orderBy('id_verification')
            ->select('id_verification', 'financial_review', 'financial_review_snapshot')
            ->chunkById(100, function ($rows) {
                foreach ($rows as $row) {
                    $merged = $this->mergedReview($row->financial_review_snapshot, $row->financial_review);

                    if ($merged === null) {
                        continue;
                    }

                    DB::table('customer_verifications')
                        ->where('id_verification', $row->id_verification)
                        ->update(['financial_review' => Purifier::clean($merged, 'ckeditor')]);
                }
            }, 'id_verification');
    }

    private function mergedReview(?string $snapshot, ?string $current): ?string
    {
        if (! $this->hasContent($snapshot)) {
            return null;
        }

        if (! $this->hasContent($current)) {
            return $snapshot;
        }

        return '<p><strong>Review Marketing</strong></p>'.$snapshot
            .'<p><strong>Catatan Admin Finance</strong></p>'.$current;
    }

    private function hasContent(?string $value): bool
    {
        return trim(strip_tags((string) $value)) !== '';
    }
};
