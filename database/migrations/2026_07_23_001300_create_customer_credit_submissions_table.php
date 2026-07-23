<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_credit_submissions', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('id_customer');

            // String biasa (bukan DB-level enum), di-cast ke PHP backed enum
            // App\Enums\CustomerCreditSubmissionType di model -- pola sama
            // dengan document_approvals.status.
            $table->string('submission_type');

            $table->integer('top_payment')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();

            $table->foreign('id_customer')
                ->references('id_customer')->on('customers')
                ->onDelete('cascade');

            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');

            $table->index(['id_customer', 'submission_type']);
        });
    }

    public function down(): void
    {
        Schema::table('customer_credit_submissions', function (Blueprint $table) {
            $table->dropForeign(['id_customer']);
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
        });

        Schema::dropIfExists('customer_credit_submissions');
    }
};
