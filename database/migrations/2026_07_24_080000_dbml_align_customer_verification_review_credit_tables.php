<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Task DBML-D (customer-kyc-dbml-alignment-migration.md) -- selaraskan
 * customer_verifications/customer_review/customer_credit_submissions/
 * customer_credit_items ke DBML artifact `21a36fda` (§12).
 *
 * Dijalankan SEBELUM Task DBML-A (migration
 * 2026_07_24_080100_dbml_align_customers_table.php) karena kolom
 * credit_limit/credit_limit_diajukan yang ditambah di sini adalah target
 * backfill Task DBML-A sebelum kolom sumbernya di `customers` di-drop.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_verifications', function (Blueprint $table) {
            $table->unsignedBigInteger('id_customer')->change();
            $table->integer('legal_result')->default(0)->change();
            $table->integer('finance_result')->default(0)->change();
            $table->integer('logistics_result')->default(0)->change();
        });

        Schema::table('customer_verifications', function (Blueprint $table) {
            $table->unique('verification_token');
        });

        Schema::table('customer_review', function (Blueprint $table) {
            $table->integer('review_result')->default(0)->change();
        });

        Schema::table('customer_credit_submissions', function (Blueprint $table) {
            $table->unsignedBigInteger('credit_limit')->default(0)->after('id_customer');
            $table->unsignedBigInteger('credit_limit_diajukan')->default(0)->after('credit_limit');
        });

        Schema::table('customer_credit_submissions', function (Blueprint $table) {
            $table->string('submission_type', 30)->change();
        });

        Schema::table('customer_credit_items', function (Blueprint $table) {
            $table->decimal('volume', 15, 2)->nullable()->change();
            $table->string('unit', 20)->nullable()->change();
            $table->decimal('existing_limit', 18, 2)->nullable()->change();
            $table->decimal('actual_payment', 18, 2)->nullable()->change();
            $table->decimal('credit_limit_request', 18, 2)->nullable()->change();
            $table->decimal('credit_limit_approval', 18, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('customer_credit_items', function (Blueprint $table) {
            $table->decimal('volume', 22, 4)->nullable()->change();
            $table->string('unit', 255)->nullable()->change();
            $table->decimal('existing_limit', 22, 4)->nullable()->change();
            $table->decimal('actual_payment', 22, 4)->nullable()->change();
            $table->decimal('credit_limit_request', 22, 4)->nullable()->change();
            $table->decimal('credit_limit_approval', 22, 4)->nullable()->change();
        });

        Schema::table('customer_credit_submissions', function (Blueprint $table) {
            $table->string('submission_type', 255)->change();
        });

        Schema::table('customer_credit_submissions', function (Blueprint $table) {
            $table->dropColumn(['credit_limit', 'credit_limit_diajukan']);
        });

        Schema::table('customer_review', function (Blueprint $table) {
            $table->smallInteger('review_result')->default(0)->change();
        });

        Schema::table('customer_verifications', function (Blueprint $table) {
            $table->dropUnique(['verification_token']);
        });

        Schema::table('customer_verifications', function (Blueprint $table) {
            $table->integer('id_customer')->change();
            $table->smallInteger('legal_result')->default(0)->change();
            $table->smallInteger('finance_result')->default(0)->change();
            $table->smallInteger('logistics_result')->default(0)->change();
        });
    }
};
