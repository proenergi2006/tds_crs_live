<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_payment', function (Blueprint $table) {
            $table->dropForeign(['prov_billing']);
            $table->dropForeign(['kab_billing']);
            $table->dropForeign(['province_id']);
            $table->dropForeign(['regency_id']);
            $table->dropForeign(['district_id']);
            $table->dropForeign(['village_id']);

            $table->dropIndex('pro_cust_payment_idx1');
            $table->dropIndex('pro_cust_payment_idx2');
            $table->dropIndex(['province_id']);
            $table->dropIndex(['regency_id']);
            $table->dropIndex(['district_id']);
            $table->dropIndex(['village_id']);

            $table->dropColumn([
                'email_billing',
                'alamat_billing',
                'prov_billing',
                'kab_billing',
                'postalcode_billing',
                'kecamatan_billing',
                'kelurahan_billing',
                'province_id',
                'regency_id',
                'district_id',
                'village_id',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('customer_payment', function (Blueprint $table) {
            $table->string('email_billing', 300)->nullable();
            $table->string('alamat_billing', 300)->nullable();
            $table->integer('prov_billing')->nullable();
            $table->integer('kab_billing')->nullable();
            $table->string('postalcode_billing', 50)->nullable();
            $table->string('kecamatan_billing', 300)->nullable();
            $table->string('kelurahan_billing', 300)->nullable();
            $table->char('province_id', 2)->nullable();
            $table->char('regency_id', 5)->nullable();
            $table->char('district_id', 8)->nullable();
            $table->char('village_id', 13)->nullable();

            $table->index('prov_billing', 'pro_cust_payment_idx1');
            $table->index('kab_billing', 'pro_cust_payment_idx2');
            $table->index('province_id');
            $table->index('regency_id');
            $table->index('district_id');
            $table->index('village_id');

            $table->foreign('prov_billing')->references('id_provinsi')->on('provinsis')->cascadeOnUpdate();
            $table->foreign('kab_billing')->references('id_kabupaten')->on('kabupatens')->cascadeOnUpdate();
            $table->foreign('province_id')->references('id')->on('provinces')->onDelete('restrict');
            $table->foreign('regency_id')->references('id')->on('regencies')->onDelete('restrict');
            $table->foreign('district_id')->references('id')->on('districts')->onDelete('restrict');
            $table->foreign('village_id')->references('id')->on('villages')->onDelete('restrict');
        });
    }
};
