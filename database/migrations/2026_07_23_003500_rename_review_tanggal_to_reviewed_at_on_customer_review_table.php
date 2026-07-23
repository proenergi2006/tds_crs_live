<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_review', function (Blueprint $table) {
            $table->renameColumn('review_tanggal', 'reviewed_at');
        });
    }

    public function down(): void
    {
        Schema::table('customer_review', function (Blueprint $table) {
            $table->renameColumn('reviewed_at', 'review_tanggal');
        });
    }
};
