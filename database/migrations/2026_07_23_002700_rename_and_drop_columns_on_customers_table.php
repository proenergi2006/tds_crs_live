<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('need_update');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->renameColumn('telepon', 'phone');
            $table->renameColumn('jenis_customer', 'customer_type');
            $table->renameColumn('nama_perusahaan', 'company_name');
            $table->renameColumn('alamat_perusahaan', 'company_address');
            $table->renameColumn('kode_pelanggan', 'customer_code');
            $table->renameColumn('website_customer', 'website');
            $table->renameColumn('tipe_bisnis_lain', 'business_type_other');
            $table->renameColumn('ownership_lain', 'ownership_type_other');
            $table->renameColumn('induk_perusahaan', 'parent_company');
            $table->renameColumn('kecamatan_customer', 'customer_sub_district');
            $table->renameColumn('kelurahan_customer', 'customer_village');
            $table->renameColumn('is_generated_link', 'is_link_generated');
            $table->renameColumn('count_update', 'update_count');
            $table->renameColumn('created_time', 'created_at');
            $table->renameColumn('lastupdate_time', 'updated_at');
            $table->renameColumn('lastupdate_by', 'updated_by');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->renameColumn('phone', 'telepon');
            $table->renameColumn('customer_type', 'jenis_customer');
            $table->renameColumn('company_name', 'nama_perusahaan');
            $table->renameColumn('company_address', 'alamat_perusahaan');
            $table->renameColumn('customer_code', 'kode_pelanggan');
            $table->renameColumn('website', 'website_customer');
            $table->renameColumn('business_type_other', 'tipe_bisnis_lain');
            $table->renameColumn('ownership_type_other', 'ownership_lain');
            $table->renameColumn('parent_company', 'induk_perusahaan');
            $table->renameColumn('customer_sub_district', 'kecamatan_customer');
            $table->renameColumn('customer_village', 'kelurahan_customer');
            $table->renameColumn('is_link_generated', 'is_generated_link');
            $table->renameColumn('update_count', 'count_update');
            $table->renameColumn('created_at', 'created_time');
            $table->renameColumn('updated_at', 'lastupdate_time');
            $table->renameColumn('updated_by', 'lastupdate_by');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->smallInteger('need_update')->default(0);
        });
    }
};
