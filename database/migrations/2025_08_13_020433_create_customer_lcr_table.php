<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
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

            // JSON/ARRAY fields
            $table->json('nama_surveyor')->nullable();    // ["vinta", ...]
            $table->text('review')->nullable();
            $table->string('jenis_usaha', 100)->nullable();
            $table->string('website', 191)->nullable();
            $table->json('hasilsurv')->nullable();         // catatan hasil survey (array)
            $table->json('produkvol')->nullable();         // [{produk, volbul}]
            $table->json('picustomer')->nullable();        // [{nama,posisi,telepon}]

            $table->string('alat_ukur', 100)->nullable();
            $table->string('toleransi', 50)->nullable();
            $table->json('kompetitor')->nullable();        // ["PT AKR", ...]
            $table->json('jam_operasional')->nullable();   // ["08.00-17.00", ...]

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

            // Teknis – JSON groups
            $table->json('tangki')->nullable();           // [{tipe,kapasitas,jumlah,produk,inlet,ukuran}]
            $table->json('pendukung')->nullable();        // [{pompa,aliran,selang,valve,ground,sinyal}]
            $table->json('quantity_tangki')->nullable();  // opsional
            $table->json('quality_tangki')->nullable();   // opsional
            $table->text('catatan_tangki')->nullable();

            $table->json('kapal')->nullable();            // opsional
            $table->json('jetty')->nullable();            // opsional
            $table->json('quantity_kapal')->nullable();   // opsional
            $table->json('quality_kapal')->nullable();    // opsional
            $table->text('catatan_kapal')->nullable();

            $table->text('penjelasan_bongkar')->nullable();

            // Koordinat & Maps
            $table->decimal('latitude_lokasi', 10, 7)->nullable();
            $table->decimal('longitude_lokasi', 10, 7)->nullable();
            $table->text('link_google_maps')->nullable();

            // Logistik lainnya
            $table->string('jarak_depot', 50)->nullable();   // disimpan sebagai string karena format bisa "39,1"
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

            // Audit
            $table->dateTime('created_time')->nullable();
            $table->string('created_ip', 45)->nullable();
            $table->string('created_by', 100)->nullable();
            $table->dateTime('lastupdate_time')->nullable();
            $table->string('lastupdate_ip', 45)->nullable();
            $table->string('lastupdate_by', 100)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_lcr');
    }
};