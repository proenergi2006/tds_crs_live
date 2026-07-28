<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_documents', function (Blueprint $table) {
            $table->renameColumn('id', 'id_document');
        });

        Schema::table('customer_documents', function (Blueprint $table) {
            // Cast ke App\Enums\CustomerDocumentStatus di model -- string biasa,
            // bukan DB-level enum.
            $table->string('status', 20)->nullable();
            $table->text('notes')->nullable();

            // Audit manual (varchar), berbeda dari `uploaded_by` (FK bigint ke users).
            $table->string('created_by', 100)->nullable();
            $table->string('updated_by', 100)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('customer_documents', function (Blueprint $table) {
            $table->dropColumn(['status', 'notes', 'created_by', 'updated_by']);
        });

        Schema::table('customer_documents', function (Blueprint $table) {
            $table->renameColumn('id_document', 'id');
        });
    }
};
