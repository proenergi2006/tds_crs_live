<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_document_types', function (Blueprint $table) {
            $table->boolean('requires_number')->default(false)->after('is_active');
        });

        Schema::table('customer_documents', function (Blueprint $table) {
            $table->string('document_number')->nullable()->after('id_document_type');
        });
    }

    public function down(): void
    {
        Schema::table('customer_documents', function (Blueprint $table) {
            $table->dropColumn('document_number');
        });

        Schema::table('customer_document_types', function (Blueprint $table) {
            $table->dropColumn('requires_number');
        });
    }
};
