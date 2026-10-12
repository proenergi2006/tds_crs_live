<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const LEGACY_APPROVABLE_TYPE = 'App\Models\PenawaranProenergi';

    public function up(): void
    {
        $this->assertMergedIntoQuotations();
        $this->assertNoLegacyApprovals();

        Schema::dropIfExists('penawaran_ongkos_proenergi');
        Schema::dropIfExists('penawaran_items_proenergi');
        Schema::dropIfExists('penawarans_proenergi');
    }

    public function down(): void
    {
        if (! Schema::hasTable('penawarans_proenergi')) {
            Schema::create('penawarans_proenergi', function (Blueprint $table) {
                $table->bigIncrements('id_penawaran');
                $table->unsignedBigInteger('id_customer');
                $table->unsignedBigInteger('id_cabang');
                $table->string('nomor_penawaran', 255)->unique();
                $table->date('masa_berlaku');
                $table->date('sampai_dengan');
                $table->decimal('subtotal', 20, 2)->default(0);
                $table->decimal('ppn11', 20, 2)->default(0);
                $table->decimal('total', 20, 2)->default(0);
                $table->string('fax', 255)->nullable();
                $table->string('tipe_pembayaran', 255)->nullable();
                $table->string('order_method', 255)->nullable();
                $table->decimal('toleransi_penyusutan', 8, 2)->nullable();
                $table->string('lokasi_pengiriman', 255)->nullable();
                $table->string('metode', 255)->nullable();
                $table->decimal('refund', 20, 2)->nullable();
                $table->decimal('other_cost', 20, 2)->nullable();
                $table->string('perhitungan', 255)->nullable();
                $table->text('keterangan')->nullable();
                $table->text('catatan')->nullable();
                $table->text('syarat_ketentuan')->nullable();
                $table->timestamp('created_at')->nullable();
                $table->string('created_by', 255)->nullable();
                $table->timestamp('updated_at')->nullable();
                $table->string('updated_by', 255)->nullable();
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
                $table->string('status', 255)->default('draft');
                $table->timestamp('approved_at')->nullable();
                $table->string('approved_by', 255)->nullable();
                $table->timestamp('rejected_at')->nullable();
                $table->string('rejected_by', 255)->nullable();
                $table->text('rejected_reason')->nullable();
                $table->smallInteger('disposisi_penawaran')->default(0);
                $table->text('catatan_verifikasi')->nullable();
                $table->string('bm_result', 255)->nullable();
                $table->timestamp('bm_tanggal')->nullable();
                $table->string('om_result', 255)->nullable();
                $table->timestamp('om_tanggal')->nullable();
                $table->text('catatan_om')->nullable();
                $table->string('abrasi', 100)->nullable();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->text('qr_code')->nullable();
                $table->decimal('harga_dasar', 15, 2)->nullable();
                $table->decimal('ppn_harga_dasar', 15, 2)->nullable();
                $table->decimal('grand_total_harga_dasar', 15, 2)->nullable();
                $table->string('type_pengiriman', 255)->nullable();
                $table->string('dp_persen', 10)->nullable();
                $table->string('dp_keterangan', 100)->nullable();
                $table->string('repayment_persen', 10)->nullable();
                $table->string('repayment_hari', 10)->nullable();
                $table->integer('top_hari')->nullable();
                $table->string('acuan_pembayaran', 50)->nullable();
                $table->string('token_verifikasi', 255)->nullable()->unique();
                $table->text('lampiran_tambahan')->nullable();
                $table->unsignedBigInteger('customer_contact_id')->nullable();

                $table->index('user_id');

                $table->foreign('id_customer')->references('id_customer')->on('customers')->cascadeOnUpdate()->restrictOnDelete();
                $table->foreign('id_cabang')->references('id_cabang')->on('cabangs')->cascadeOnUpdate()->restrictOnDelete();
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnUpdate()->nullOnDelete();
                $table->foreign('customer_contact_id')->references('id_contact')->on('customer_contacts')->restrictOnDelete();
            });
        }

        if (! Schema::hasTable('penawaran_items_proenergi')) {
            Schema::create('penawaran_items_proenergi', function (Blueprint $table) {
                $table->bigIncrements('id_penawaran_item');
                $table->unsignedBigInteger('id_penawaran');
                $table->unsignedBigInteger('id_produk');
                $table->decimal('volume_order', 20, 2)->default(0);
                $table->decimal('harga_tebus', 20, 2)->default(0);
                $table->decimal('jumlah_harga', 30, 2)->default(0);
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
                $table->decimal('persen', 5, 2)->default(0);

                $table->foreign('id_penawaran')->references('id_penawaran')->on('penawarans_proenergi')->cascadeOnUpdate()->cascadeOnDelete();
                $table->foreign('id_produk')->references('id_produk')->on('produks')->cascadeOnUpdate()->restrictOnDelete();
            });
        }

        if (! Schema::hasTable('penawaran_ongkos_proenergi')) {
            Schema::create('penawaran_ongkos_proenergi', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('penawaran_id');
                $table->unsignedBigInteger('wilayah_id');
                $table->unsignedBigInteger('transportir_id');
                $table->decimal('ongkos', 15, 2);
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
                $table->unsignedBigInteger('volume_id')->nullable();
                $table->string('jenis', 10)->nullable();

                $table->foreign('penawaran_id')->references('id_penawaran')->on('penawarans_proenergi')->cascadeOnUpdate()->cascadeOnDelete();
                $table->foreign('wilayah_id')->references('id')->on('transport_areas')->cascadeOnUpdate()->restrictOnDelete();
                $table->foreign('transportir_id')->references('id')->on('transporters')->cascadeOnUpdate()->restrictOnDelete();
                $table->foreign('volume_id')->references('id')->on('volumes')->cascadeOnUpdate()->restrictOnDelete();
            });
        }
    }

    private function assertMergedIntoQuotations(): void
    {
        if (! Schema::hasTable('penawarans_proenergi')) {
            return;
        }

        if (! Schema::hasTable('quotations')) {
            throw new RuntimeException('quotations does not exist; cannot confirm penawarans_proenergi was merged before dropping it.');
        }

        $unmerged = DB::table('penawarans_proenergi as legacy')
            ->leftJoin('quotations', 'quotations.nomor_penawaran', '=', 'legacy.nomor_penawaran')
            ->whereNull('quotations.id')
            ->pluck('legacy.nomor_penawaran');

        if ($unmerged->isNotEmpty()) {
            throw new RuntimeException('penawarans_proenergi holds quotations missing from quotations: ' . $unmerged->implode(', ') . ' — bring this to Engineer before dropping.');
        }
    }

    private function assertNoLegacyApprovals(): void
    {
        $count = DB::table('document_approvals')
            ->where('approvable_type', self::LEGACY_APPROVABLE_TYPE)
            ->count();

        if ($count > 0) {
            throw new RuntimeException("document_approvals still has {$count} rows for " . self::LEGACY_APPROVABLE_TYPE . ' — bring this to Engineer before dropping.');
        }
    }
};
