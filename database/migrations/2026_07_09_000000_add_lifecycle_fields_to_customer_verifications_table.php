<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('customer_verifications', function (Blueprint $table) {
            $table->timestamp('expired_at')->nullable()->after('is_active');
            $table->string('completion_status', 20)->default('draft')->after('expired_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_verifications', function (Blueprint $table) {
            $table->dropColumn(['expired_at', 'completion_status']);
        });
    }
};
