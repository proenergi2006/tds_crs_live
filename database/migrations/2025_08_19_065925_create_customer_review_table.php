<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('customer_review', function (Blueprint $t) {
            $t->increments('id_review');
            // sesuaikan nama tabel FK: pakai 'customer_verifications' (bukan pro_customer_verification)
            $t->unsignedInteger('id_verification');

            // 16 kolom review (opsional dipakai untuk catatan singkat poin2 tertentu)
            for ($i = 1; $i <= 16; $i++) {
                $t->string("review{$i}", 500)->nullable();
            }

            $t->tinyInteger('review_result')->default(0); // 0=pending, 1=ok, dst (bebas)
            $t->string('review_pic', 50)->nullable();
            $t->dateTime('review_tanggal')->nullable();
            $t->text('review_summary')->nullable();

            // ringkasan file utama (opsional — kalau mau simpan 1 file “utama”)
            $t->string('review_attach', 250)->nullable();
            $t->string('review_attach_ori', 250)->nullable();

            // field khusus mapping dari form kamu (Informasi Umum)
            $t->string('jenis_asset', 500)->nullable();               // 2.5
            $t->string('kelengkapan_dok_tagihan', 500)->nullable();   // 2.6
            $t->string('alur_proses_periksaan', 500)->nullable();     // 2.7
            $t->string('jadwal_penerimaan', 500)->nullable();         // 2.8
            $t->string('background_bisnis', 500)->nullable();         // 2.11
            $t->string('lokasi_depo', 500)->nullable();               // 2.12
            $t->string('opportunity_bisnis', 500)->nullable();        // 2.13

            $t->foreign('id_verification')
              ->references('id_verification')->on('customer_verifications')
              ->cascadeOnUpdate()->cascadeOnDelete(); // jika tidak ingin delete cascade, ganti ke restrict
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_review');
    }
};
