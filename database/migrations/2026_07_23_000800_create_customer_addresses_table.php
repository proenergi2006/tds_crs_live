<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_addresses', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('id_customer');

            // String biasa (bukan DB-level enum), di-cast ke PHP backed enum
            // App\Enums\CustomerAddressType di model -- pola sama dengan
            // document_approvals.status.
            $table->string('address_type');

            $table->text('address_line');

            // Sistem alamat BPS, pola & tipe kolom sama persis dengan
            // customers.province_id/regency_id/district_id/village_id.
            $table->char('province_id', 2)->nullable();
            $table->char('regency_id', 5)->nullable();
            $table->char('district_id', 8)->nullable();
            $table->char('village_id', 13)->nullable();

            $table->string('postal_code')->nullable();
            $table->boolean('is_primary')->default(false);

            $table->timestamps();

            $table->foreign('id_customer')
                ->references('id_customer')->on('customers')
                ->onDelete('cascade');

            $table->foreign('province_id')->references('id')->on('provinces')->onDelete('restrict');
            $table->foreign('regency_id')->references('id')->on('regencies')->onDelete('restrict');
            $table->foreign('district_id')->references('id')->on('districts')->onDelete('restrict');
            $table->foreign('village_id')->references('id')->on('villages')->onDelete('restrict');

            $table->index(['id_customer', 'address_type']);
        });
    }

    public function down(): void
    {
        Schema::table('customer_addresses', function (Blueprint $table) {
            $table->dropForeign(['id_customer']);
            $table->dropForeign(['province_id']);
            $table->dropForeign(['regency_id']);
            $table->dropForeign(['district_id']);
            $table->dropForeign(['village_id']);
        });

        Schema::dropIfExists('customer_addresses');
    }
};
