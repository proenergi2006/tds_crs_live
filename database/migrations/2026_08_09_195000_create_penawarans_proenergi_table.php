<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Direkonstruksi dari backup dump (2026-08-20) -- tabel ini historically dibuat manual
// langsung di DB, gak pernah punya migration create_table di repo manapun. Kolom
// token_verifikasi sengaja gak disertakan di sini, itu udah ditambah migration
// 2026_08_09_200000 yang jalan setelah ini.
return new class extends Migration
{
    public function up(): void
    {
        // Guard: production kemungkinan sudah punya tabel ini dari pembuatan manual dulu -- no-op kalau sudah ada.
        if (Schema::hasTable('penawarans_proenergi')) {
            return;
        }

        Schema::create('penawarans_proenergi', function (Blueprint $table) {
            $table->id('id_penawaran');
            $table->unsignedBigInteger('id_customer');
            $table->unsignedBigInteger('id_cabang');
            $table->string('nomor_penawaran')->unique();
            $table->date('masa_berlaku');
            $table->date('sampai_dengan');
            $table->decimal('subtotal', 20, 2)->default(0);
            $table->decimal('ppn11', 20, 2)->default(0);
            $table->decimal('total', 20, 2)->default(0);
            $table->string('kepada')->nullable();
            $table->string('jabatan')->nullable();
            $table->string('telepon')->nullable();
            $table->string('nama')->nullable();
            $table->text('alamat')->nullable();
            $table->string('fax')->nullable();
            $table->string('tipe_pembayaran')->nullable();
            $table->string('order_method')->nullable();
            $table->decimal('toleransi_penyusutan', 8, 2)->nullable();
            $table->string('lokasi_pengiriman')->nullable();
            $table->string('metode')->nullable();
            $table->decimal('refund', 20, 2)->nullable();
            $table->decimal('other_cost', 20, 2)->nullable();
            $table->string('perhitungan')->nullable();
            $table->text('keterangan')->nullable();
            $table->text('catatan')->nullable();
            $table->text('syarat_ketentuan')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->string('updated_by')->nullable();
            $table->decimal('discount', 8, 2)->default(0);
            $table->decimal('harga_tebus_setelah_diskon', 15, 2)->default(0);
            $table->decimal('total_with_oat', 15, 2)->default(0);
            $table->integer('oat')->default(0);
            $table->decimal('biaya_kirim', 15, 2)->default(0);
            $table->decimal('diskon', 5, 2)->default(0);
            $table->string('jenis_penawaran', 50)->nullable();
            $table->decimal('oa_kapal', 12, 2)->nullable();
            $table->decimal('oa_truck', 12, 2)->nullable();
            $table->boolean('penawaran_disetujui')->default(false);
            $table->string('status')->default('draft');
            $table->timestamp('approved_at')->nullable();
            $table->string('approved_by')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->string('rejected_by')->nullable();
            $table->text('rejected_reason')->nullable();
            $table->smallInteger('disposisi_penawaran')->default(0);
            $table->text('catatan_verifikasi')->nullable();
            $table->string('bm_result')->nullable();
            $table->timestamp('bm_tanggal')->nullable();
            $table->string('om_result')->nullable();
            $table->timestamp('om_tanggal')->nullable();
            $table->text('catatan_om')->nullable();
            $table->string('abrasi', 100)->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->text('qr_code')->nullable();
            $table->decimal('harga_dasar', 15, 2)->nullable();
            $table->decimal('ppn_harga_dasar', 15, 2)->nullable();
            $table->decimal('grand_total_harga_dasar', 15, 2)->nullable();
            $table->string('type_pengiriman')->nullable();
            $table->string('dp_persen', 10)->nullable();
            $table->string('dp_keterangan', 100)->nullable();
            $table->string('repayment_persen', 10)->nullable();
            $table->string('repayment_hari', 10)->nullable();
            $table->integer('top_hari')->nullable();
            $table->string('acuan_pembayaran', 50)->nullable();

            $table->foreign('id_cabang')->references('id_cabang')->on('cabangs')->onUpdate('cascade')->onDelete('restrict');
            $table->foreign('id_customer')->references('id_customer')->on('customers')->onUpdate('cascade')->onDelete('restrict');
            $table->foreign('user_id')->references('id')->on('users')->onUpdate('cascade')->onDelete('set null');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penawarans_proenergi');
    }
};
