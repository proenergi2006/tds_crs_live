<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('po_customers', function (Blueprint $table) {
            $table->id('id_poc');
            $table->unsignedBigInteger('id_customer');
            $table->unsignedBigInteger('id_penawaran');

            $table->string('top_poc', 20);
            $table->string('nomor_poc', 50);
            $table->date('tanggal_poc');
            $table->date('supply_date')->nullable();

            $table->decimal('harga_poc', 22, 4);
            $table->integer('volume_poc');
            $table->unsignedBigInteger('produk_poc');

            $table->string('lampiran_poc', 250);
            $table->string('lampiran_poc_ori', 250);

            $table->tinyInteger('disposisi_poc')->default(0);
            $table->tinyInteger('poc_approved')->default(0)->nullable();
            $table->dateTime('tgl_approved')->nullable();

            $table->tinyInteger('sm_result')->default(0)->nullable();
            $table->string('sm_pic', 50)->nullable();
            $table->text('sm_summary')->nullable();
            $table->dateTime('sm_tanggal')->nullable();

            $table->tinyInteger('om_result')->default(0)->nullable();
            $table->string('om_pic', 50)->nullable();
            $table->text('om_summary')->nullable();
            $table->dateTime('om_tanggal')->nullable();

            $table->dateTime('created_time');
            $table->string('created_ip', 20);
            $table->string('created_by', 50);

            $table->dateTime('lastupdate_time')->nullable();
            $table->string('lastupdate_ip', 20)->nullable();
            $table->string('lastupdate_by', 50)->nullable();

            $table->integer('po_notif')->nullable();
            $table->string('st_bayar_po', 1)->default('T');
            $table->dateTime('tgl_bayar_po')->nullable();
            $table->string('keterangan_bayar', 1000)->nullable();
            $table->tinyInteger('is_edit')->default(0);

            // Tambahkan index & constraint foreign key jika perlu
            $table->foreign('id_customer')->references('id_customer')->on('customers')->cascadeOnDelete();
            $table->foreign('id_penawaran')->references('id_penawaran')->on('penawarans')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('po_customers');
    }
};
