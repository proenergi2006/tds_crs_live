<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_verifications', function (Blueprint $table) {
            $table->dropColumn(['legal_data', 'legal_summary', 'legal_result', 'legal_processed_at', 'legal_pic']);
        });
    }

    public function down(): void
    {
        Schema::table('customer_verifications', function (Blueprint $table) {
            $table->text('legal_data')->nullable();
            $table->text('legal_summary')->nullable();
            $table->integer('legal_result')->default(0);
            $table->dateTime('legal_processed_at')->nullable();
            $table->string('legal_pic', 50)->nullable();
        });
    }
};
