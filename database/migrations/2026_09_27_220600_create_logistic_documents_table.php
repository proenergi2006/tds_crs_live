<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('logistic_documents', function (Blueprint $table) {
            $table->id();
            $table->string('documentable_type', 20);
            $table->unsignedBigInteger('documentable_id');
            $table->string('document_type', 30);
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->date('valid_until')->nullable();
            $table->timestamp('uploaded_at')->nullable();
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->timestamps();

            $table->foreign('uploaded_by')->references('id')->on('users')->nullOnDelete();

            $table->index(['documentable_type', 'documentable_id'], 'logistic_documents_documentable_idx');
            $table->index(['documentable_type', 'documentable_id', 'document_type'], 'logistic_documents_documentable_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logistic_documents');
    }
};
