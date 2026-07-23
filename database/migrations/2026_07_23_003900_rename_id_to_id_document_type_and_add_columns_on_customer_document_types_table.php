<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_document_types', function (Blueprint $table) {
            $table->renameColumn('id', 'id_document_type');
        });

        Schema::table('customer_document_types', function (Blueprint $table) {
            $table->string('category', 50)->nullable();
            $table->integer('sort_order')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('customer_document_types', function (Blueprint $table) {
            $table->dropColumn(['category', 'sort_order']);
        });

        Schema::table('customer_document_types', function (Blueprint $table) {
            $table->renameColumn('id_document_type', 'id');
        });
    }
};
