<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sales Review dilepas dari token onboarding (`id_verification`) dan
     * di-key langsung ke `id_customer`. 0 row live saat migration ini
     * ditulis, jadi tidak ada migrasi data.
     */
    public function up(): void
    {
        Schema::table('customer_review', function (Blueprint $table) {
            $table->dropForeign('customer_review_id_verification_foreign');
            $table->dropColumn(['id_verification', 'review_result', 'review_pic', 'review_summary']);
        });

        Schema::table('customer_review', function (Blueprint $table) {
            $table->unsignedBigInteger('id_customer')->after('id_review');
            $table->unique('id_customer');
            $table->foreign('id_customer')->references('id_customer')->on('customers')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('customer_review', function (Blueprint $table) {
            $table->dropForeign(['id_customer']);
            $table->dropColumn('id_customer');
        });

        Schema::table('customer_review', function (Blueprint $table) {
            $table->unsignedInteger('id_verification')->after('id_review');
            $table->tinyInteger('review_result')->default(0);
            $table->string('review_pic', 50)->nullable();
            $table->text('review_summary')->nullable();

            $table->foreign('id_verification')
                ->references('id_verification')->on('customer_verifications')
                ->cascadeOnUpdate()->cascadeOnDelete();
        });
    }
};
