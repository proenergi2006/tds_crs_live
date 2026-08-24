<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // requires_number pindah jadi hardcoded check terhadap code (nib/npwp) di app-layer.
    public function up(): void
    {
        Schema::table('customer_document_types', function (Blueprint $table) {
            $table->dropColumn('requires_number');
        });
    }

    public function down(): void
    {
        Schema::table('customer_document_types', function (Blueprint $table) {
            $table->boolean('requires_number')->default(false)->after('is_active');
        });
    }
};
