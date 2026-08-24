<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Nullable: tipe kontak cuma dipasang otomatis untuk site PIC; kontak level-company cukup pakai position.
    public function up(): void
    {
        Schema::table('customer_contacts', function (Blueprint $table) {
            $table->unsignedBigInteger('id_contact_type')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('customer_contacts', function (Blueprint $table) {
            $table->unsignedBigInteger('id_contact_type')->nullable(false)->change();
        });
    }
};
