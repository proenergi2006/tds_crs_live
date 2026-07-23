<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_documents', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('id_customer');
            $table->unsignedBigInteger('id_document_type');

            $table->string('file_path');
            $table->string('file_name');

            $table->timestamp('uploaded_at')->nullable();
            $table->unsignedBigInteger('uploaded_by')->nullable();

            $table->timestamps();

            $table->foreign('id_customer')
                ->references('id_customer')->on('customers')
                ->onDelete('cascade');

            $table->foreign('id_document_type')
                ->references('id')->on('customer_document_types')
                ->onDelete('restrict');

            $table->foreign('uploaded_by')
                ->references('id')->on('users')
                ->onDelete('set null');

            $table->index(['id_customer', 'id_document_type']);
        });
    }

    public function down(): void
    {
        Schema::table('customer_documents', function (Blueprint $table) {
            $table->dropForeign(['id_customer']);
            $table->dropForeign(['id_document_type']);
            $table->dropForeign(['uploaded_by']);
        });

        Schema::dropIfExists('customer_documents');
    }
};
