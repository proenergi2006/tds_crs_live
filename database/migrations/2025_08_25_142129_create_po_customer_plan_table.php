<?php

// database/migrations/2025_08_25_000000_create_po_customer_plan_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('po_customer_plan', function (Blueprint $table) {
            $table->increments('id_plan');                    // PK
            $table->bigInteger('id_poc');                     // FK ke po_customers.id_poc (opsional tambahkan constraint)
            $table->integer('id_lcr');                        // id lokasi/relasi lain (kalau ada)
            $table->date('tanggal_kirim')->nullable();
            $table->integer('volume_kirim')->nullable();
            $table->integer('realisasi_kirim')->default(0);
            $table->tinyInteger('is_urgent')->default(0);
            $table->string('top_plan', 20)->nullable();
            $table->string('actual_top_plan', 50)->nullable();
            $table->string('pelanggan_plan', 30)->nullable();
            $table->integer('ar_notyet')->nullable();
            $table->integer('ar_satu')->nullable();
            $table->integer('ar_dua')->nullable();
            $table->integer('kredit_limit')->nullable();
            $table->tinyInteger('status_plan')->default(0)->comment('0:terdaftar, 1:masuk_pr, 2:reschedule, 3:pending');
            $table->string('status_jadwal', 1500)->nullable(); // dipakai juga utk simpan alamat/catatan ringkas
            $table->tinyInteger('ask_approval')->default(0);
            $table->text('catatan_reschedule')->nullable();
            $table->tinyInteger('is_approved')->default(1);

            $table->dateTime('created_time');
            $table->string('created_ip', 20);
            $table->string('created_by', 50);

            $table->integer('splitted_from_plan')->nullable();
            $table->integer('vol_ori_plan')->nullable();

            $table->index(['id_poc']);
        });

        // (Opsional) kalau ingin FK dan DB mendukung
        // Schema::table('po_customer_plan', function (Blueprint $table) {
        //     $table->foreign('id_poc')->references('id_poc')->on('po_customers')->onDelete('cascade');
        // });
    }

    public function down(): void
    {
        // Schema::table('po_customer_plan', fn (Blueprint $t) => $t->dropForeign(['id_poc']));
        Schema::dropIfExists('po_customer_plan');
    }
};
