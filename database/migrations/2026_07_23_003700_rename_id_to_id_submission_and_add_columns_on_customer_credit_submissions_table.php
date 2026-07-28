<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_credit_submissions', function (Blueprint $table) {
            $table->renameColumn('id', 'id_submission');
        });

        Schema::table('customer_credit_submissions', function (Blueprint $table) {
            $table->text('financial_review_notes')->nullable();
            $table->unsignedBigInteger('submitted_by')->nullable();
            $table->dateTime('submitted_at')->nullable();

            $table->foreign('submitted_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('customer_credit_submissions', function (Blueprint $table) {
            $table->dropForeign(['submitted_by']);
            $table->dropColumn(['financial_review_notes', 'submitted_by', 'submitted_at']);
        });

        Schema::table('customer_credit_submissions', function (Blueprint $table) {
            $table->renameColumn('id_submission', 'id');
        });
    }
};
