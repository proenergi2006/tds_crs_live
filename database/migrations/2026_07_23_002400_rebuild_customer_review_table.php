<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Konsolidasi `review1`-`review16` + kolom bernama (`jenis_asset`, dst)
     * jadi `review_answers` (json, array of {question_code, question, answer}),
     * dan `review_attach`/`review_attach_ori` + tabel terpisah
     * `customer_review_attchment` jadi `review_attachments` (json, array of
     * {path, url, original_name}). Tabel live minim data dummy saat migration
     * ini ditulis, jadi tidak ada migrasi data.
     */
    public function up(): void
    {
        Schema::dropIfExists('customer_review_attchment');
        Schema::dropIfExists('customer_review');

        Schema::create('customer_review', function (Blueprint $table) {
            $table->increments('id_review');
            $table->unsignedInteger('id_verification');

            $table->tinyInteger('review_result')->default(0);
            $table->string('review_pic', 50)->nullable();
            $table->dateTime('review_tanggal')->nullable();
            $table->text('review_summary')->nullable();

            $table->json('review_answers')->nullable();
            $table->json('review_attachments')->nullable();

            $table->foreign('id_verification')
                ->references('id_verification')->on('customer_verifications')
                ->cascadeOnUpdate()->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_review');

        Schema::create('customer_review', function (Blueprint $table) {
            $table->increments('id_review');
            $table->unsignedInteger('id_verification');

            for ($i = 1; $i <= 16; $i++) {
                $table->string("review{$i}", 500)->nullable();
            }

            $table->tinyInteger('review_result')->default(0);
            $table->string('review_pic', 50)->nullable();
            $table->dateTime('review_tanggal')->nullable();
            $table->text('review_summary')->nullable();

            $table->string('review_attach', 250)->nullable();
            $table->string('review_attach_ori', 250)->nullable();

            $table->string('jenis_asset', 500)->nullable();
            $table->string('kelengkapan_dok_tagihan', 500)->nullable();
            $table->string('alur_proses_periksaan', 500)->nullable();
            $table->string('jadwal_penerimaan', 500)->nullable();
            $table->string('background_bisnis', 500)->nullable();
            $table->string('lokasi_depo', 500)->nullable();
            $table->string('opportunity_bisnis', 500)->nullable();

            $table->foreign('id_verification')
                ->references('id_verification')->on('customer_verifications')
                ->cascadeOnUpdate()->cascadeOnDelete();
        });

        Schema::create('customer_review_attchment', function (Blueprint $table) {
            $table->unsignedInteger('id_review');
            $table->unsignedInteger('id_verification');
            $table->unsignedInteger('no_urut');

            $table->string('review_attach', 500)->nullable();
            $table->string('review_attach_ori', 500)->nullable();

            $table->primary(['id_review', 'id_verification', 'no_urut']);
            $table->foreign('id_review')->references('id_review')->on('customer_review')
                ->cascadeOnUpdate()->cascadeOnDelete();
        });
    }
};
