<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_contacts', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('id_customer');
            $table->unsignedBigInteger('id_contact_type');

            // Nullable: hanya diisi untuk kontak yang scoped ke 1 site survei
            // (mis. "Site PIC"), bukan kontak level perusahaan.
            $table->unsignedBigInteger('id_lcr')->nullable();

            $table->string('full_name')->nullable();
            $table->string('position')->nullable();
            $table->string('phone')->nullable();
            $table->string('mobile')->nullable();
            $table->string('email')->nullable();

            $table->timestamps();

            $table->foreign('id_customer')
                ->references('id_customer')->on('customers')
                ->onDelete('cascade');

            $table->foreign('id_contact_type')
                ->references('id')->on('customer_contact_types')
                ->onDelete('restrict');

            $table->foreign('id_lcr')
                ->references('id_lcr')->on('customer_lcr')
                ->onDelete('set null');

            $table->index(['id_customer', 'id_contact_type']);
        });
    }

    public function down(): void
    {
        Schema::table('customer_contacts', function (Blueprint $table) {
            $table->dropForeign(['id_customer']);
            $table->dropForeign(['id_contact_type']);
            $table->dropForeign(['id_lcr']);
        });

        Schema::dropIfExists('customer_contacts');
    }
};
