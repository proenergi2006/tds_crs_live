<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_review', function (Blueprint $table) {
            $table->longText('notes')->nullable()->after('review_attachments');
        });
    }

    public function down(): void
    {
        Schema::table('customer_review', function (Blueprint $table) {
            $table->dropColumn('notes');
        });
    }
};
