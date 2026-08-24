<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Nullable krn dokumen bebas pakai document_name, bukan tipe system-defined; XOR-nya ditegakkan app-layer.
    public function up(): void
    {
        Schema::table('customer_documents', function (Blueprint $table) {
            $table->unsignedBigInteger('id_document_type')->nullable()->change();
            $table->string('document_name')->nullable()->after('id_document_type');
        });
    }

    public function down(): void
    {
        Schema::table('customer_documents', function (Blueprint $table) {
            $table->dropColumn('document_name');
            $table->unsignedBigInteger('id_document_type')->nullable(false)->change();
        });
    }
};
