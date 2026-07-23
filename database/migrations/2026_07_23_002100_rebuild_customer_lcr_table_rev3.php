<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rebuild total `customer_lcr`: 7 grup mengikuti alur survei lapangan di
     * lokasi customer, ditambah grup Vessel/Jetty untuk moda pengiriman kapal.
     * Tabel live kosong (0 baris) saat migration ini ditulis, jadi tidak ada
     * migrasi data.
     *
     * `customer_contacts.id_lcr` FK harus di-drop dulu sebelum tabel ini
     * di-drop (Postgres menolak drop tabel yang masih direferensikan FK),
     * lalu di-re-add setelah tabel baru dibuat.
     */
    public function up(): void
    {
        Schema::table('customer_contacts', function (Blueprint $table) {
            $table->dropForeign(['id_lcr']);
        });

        Schema::dropIfExists('customer_lcr');

        Schema::create('customer_lcr', function (Blueprint $table) {
            $table->bigIncrements('id_lcr');
            $table->unsignedBigInteger('id_customer')->index();

            /* Grup 1: Identitas & info umum */
            $table->string('site_name')->nullable();
            $table->text('survey_address')->nullable();
            $table->unsignedInteger('prov_survey')->nullable();
            $table->unsignedInteger('kab_survey')->nullable();
            $table->date('survey_date')->nullable();
            $table->json('surveyor_names')->nullable();
            $table->string('site_business_type', 100)->nullable();
            $table->text('site_business_type_other')->nullable();
            $table->string('site_environment', 100)->nullable();
            $table->string('site_environment_other', 100)->nullable();
            $table->text('site_environment_notes')->nullable();
            $table->json('competitors')->nullable();
            $table->json('operating_hours')->nullable();
            $table->json('product_volume')->nullable();
            $table->text('survey_notes')->nullable();
            // Field lama tanpa padanan jelas di taksonomi grup baru -- dipertahankan apa adanya
            $table->json('picustomer')->nullable();
            $table->string('website', 191)->nullable();
            $table->string('telp_survey', 50)->nullable();
            $table->string('fax_survey', 50)->nullable();
            $table->unsignedBigInteger('id_wilayah')->nullable()->index();
            $table->unsignedBigInteger('id_wil_oa')->nullable()->index();

            /* Grup 2: Akses & rute */
            $table->decimal('max_truck_capacity_min', 8, 2)->nullable();
            $table->decimal('max_truck_capacity_max', 8, 2)->nullable();
            $table->text('access_notes')->nullable();
            $table->json('route_costs')->nullable();
            $table->string('distance_from_depot', 50)->nullable();
            $table->json('road_condition_photos')->nullable();
            $table->string('min_vol_kirim', 50)->nullable();
            // Field lama tanpa padanan jelas di taksonomi grup baru -- dipertahankan apa adanya
            $table->text('rute_lokasi')->nullable();
            $table->text('note_lokasi')->nullable();

            /* Grup 3: Layout & unloading truk */
            $table->json('site_layout_photos')->nullable();
            $table->string('unloading_method', 100)->nullable();
            $table->unsignedInteger('max_trucks_per_day')->nullable();
            $table->json('unloading_layout_photos')->nullable();
            $table->text('unloading_notes')->nullable();

            /* Grup 4: Penyimpanan */
            $table->string('storage_type', 100)->nullable();
            $table->string('storage_type_other', 100)->nullable();
            $table->string('storage_capacity', 100)->nullable();
            $table->text('storage_notes')->nullable();
            $table->json('storage_facility_photos')->nullable();

            /* Grup 5: Verifikasi quality/quantity */
            $table->string('quality_checking_method', 100)->nullable();
            $table->text('quality_checking_notes')->nullable();
            $table->string('quantity_checking_method', 100)->nullable();
            $table->text('quantity_checking_notes')->nullable();
            $table->json('measurement_evidence_photos')->nullable();

            /* Grup 6: Vessel/Jetty (moda kapal, baru seluruhnya) */
            $table->boolean('supports_vessel_delivery')->default(false);
            $table->string('vessel_type', 50)->nullable();
            $table->string('vessel_cargo_capacity', 100)->nullable();
            $table->string('vessel_unloading_method', 50)->nullable();
            $table->string('vessel_quantity_checking_method', 50)->nullable();
            $table->text('vessel_quantity_checking_notes')->nullable();
            $table->string('vessel_quality_checking_method', 100)->nullable();
            $table->text('vessel_quality_checking_notes')->nullable();
            $table->json('vessel_layout_photos')->nullable();
            // Commodity-agnostic, tidak terikat 1 vessel_type tertentu
            $table->string('jetty_type', 100)->nullable();
            $table->decimal('max_loa', 8, 2)->nullable();
            $table->decimal('min_pbl', 8, 2)->nullable();
            $table->decimal('draft_lws', 8, 2)->nullable();
            $table->decimal('jetty_capacity_dwt', 12, 2)->nullable();
            $table->text('jetty_permit_info')->nullable();
            $table->text('document_requirements')->nullable();

            /* Grup 7: Foto lain & lokasi */
            $table->json('company_office_photos')->nullable();
            $table->json('additional_photos')->nullable();
            $table->decimal('latitude_lokasi', 10, 7)->nullable();
            $table->decimal('longitude_lokasi', 10, 7)->nullable();
            $table->text('link_google_maps')->nullable();

            /* Audit */
            $table->dateTime('created_time')->nullable();
            $table->string('created_ip', 45)->nullable();
            $table->string('created_by', 100)->nullable();
            $table->dateTime('lastupdate_time')->nullable();
            $table->string('lastupdate_ip', 45)->nullable();
            $table->string('lastupdate_by', 100)->nullable();
        });

        Schema::table('customer_contacts', function (Blueprint $table) {
            $table->foreign('id_lcr')
                ->references('id_lcr')->on('customer_lcr')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('customer_contacts', function (Blueprint $table) {
            $table->dropForeign(['id_lcr']);
        });

        Schema::dropIfExists('customer_lcr');

        Schema::create('customer_lcr', function (Blueprint $table) {
            $table->bigIncrements('id_lcr');

            $table->unsignedBigInteger('id_customer')->index();
            $table->unsignedBigInteger('id_wilayah')->nullable()->index();
            $table->unsignedBigInteger('id_wil_oa')->nullable()->index();

            $table->text('alamat_survey')->nullable();
            $table->unsignedInteger('prov_survey')->nullable();
            $table->unsignedInteger('kab_survey')->nullable();
            $table->string('telp_survey', 50)->nullable();
            $table->string('fax_survey', 50)->nullable();
            $table->date('tgl_survey')->nullable();

            $table->json('nama_surveyor')->nullable();
            $table->text('review')->nullable();
            $table->string('jenis_usaha', 100)->nullable();
            $table->string('website', 191)->nullable();
            $table->json('hasilsurv')->nullable();
            $table->json('produkvol')->nullable();
            $table->json('picustomer')->nullable();

            $table->string('alat_ukur', 100)->nullable();
            $table->string('toleransi', 50)->nullable();
            $table->json('kompetitor')->nullable();
            $table->json('jam_operasional')->nullable();

            $table->text('logistik_summary')->nullable();
            $table->tinyInteger('logistik_result')->nullable();
            $table->dateTime('logistik_tanggal')->nullable();
            $table->string('logistik_pic', 100)->nullable();

            $table->text('sm_summary')->nullable();
            $table->tinyInteger('sm_result')->nullable();
            $table->dateTime('sm_tanggal')->nullable();
            $table->string('sm_pic', 100)->nullable();

            $table->tinyInteger('flag_disposisi')->default(0);
            $table->tinyInteger('flag_approval')->default(0);
            $table->dateTime('tgl_approval')->nullable();

            $table->json('tangki')->nullable();
            $table->json('pendukung')->nullable();
            $table->json('quantity_tangki')->nullable();
            $table->json('quality_tangki')->nullable();
            $table->text('catatan_tangki')->nullable();

            $table->json('kapal')->nullable();
            $table->json('jetty')->nullable();
            $table->json('quantity_kapal')->nullable();
            $table->json('quality_kapal')->nullable();
            $table->text('catatan_kapal')->nullable();

            $table->text('penjelasan_bongkar')->nullable();

            $table->decimal('latitude_lokasi', 10, 7)->nullable();
            $table->decimal('longitude_lokasi', 10, 7)->nullable();
            $table->text('link_google_maps')->nullable();

            $table->string('jarak_depot', 50)->nullable();
            $table->string('max_truk', 50)->nullable();
            $table->string('lsm_portal', 50)->nullable();
            $table->string('min_vol_kirim', 50)->nullable();

            $table->text('rute_lokasi')->nullable();
            $table->text('note_lokasi')->nullable();
            $table->text('layout_lokasi')->nullable();
            $table->text('layout_bongkar')->nullable();
            $table->text('kondisi_jalan')->nullable();
            $table->text('kantor_perusahaan')->nullable();
            $table->text('fasilitas_storage')->nullable();
            $table->text('inlet_pipa')->nullable();
            $table->text('alat_ukur_gambar')->nullable();
            $table->text('media_datar')->nullable();
            $table->text('keterangan_lain')->nullable();
            $table->text('jenis_usaha_lain')->nullable();

            $table->dateTime('created_time')->nullable();
            $table->string('created_ip', 45)->nullable();
            $table->string('created_by', 100)->nullable();
            $table->dateTime('lastupdate_time')->nullable();
            $table->string('lastupdate_ip', 45)->nullable();
            $table->string('lastupdate_by', 100)->nullable();
        });

        Schema::table('customer_contacts', function (Blueprint $table) {
            $table->foreign('id_lcr')
                ->references('id_lcr')->on('customer_lcr')
                ->onDelete('set null');
        });
    }
};
