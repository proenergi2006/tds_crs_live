<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sales_confirmation', function (Blueprint $table) {
            $table->bigIncrements('id');

            // 1 POC : 1 Sales Confirmation (tidak dipasang FK agar fleksibel ke tabel POC kamu)
            $table->bigInteger('po_customer_id')->unique()->index();
            $table->bigInteger('id_customer')->index();

            // Financials (pakai numeric besar untuk Postgres)
            $table->decimal('credit_limit', 22, 2)->default(0);
            $table->decimal('not_yet',      22, 2)->default(0);
            $table->decimal('ov_up_07',     22, 2)->default(0);
            $table->decimal('ov_under_30',  22, 2)->default(0);
            $table->decimal('ov_under_60',  22, 2)->default(0);
            $table->decimal('ov_under_90',  22, 2)->default(0);
            $table->decimal('ov_up_90',     22, 2)->default(0);

            // PO info
            $table->string('po_status', 50)->nullable();      // 'new' | 'partial' | ...
            $table->decimal('po_volume', 22, 2)->default(0);  // DALAM LITER
            $table->decimal('po_amount', 22, 2)->default(0);  // total rupiah
            $table->string('reminding', 255)->nullable();

            // Proposal / Unblock
            $table->smallInteger('proposed_status')->default(0); // 0=tidak,1=propose
            $table->smallInteger('add_top')->default(0);
            $table->smallInteger('add_cl')->default(0);

            // Disposisi & Approval flags
            $table->smallInteger('disposisi')->default(0)->index();
            $table->smallInteger('flag_approval')->default(0);
            $table->string('role_approved', 50)->nullable();
            $table->timestamp('tgl_approved')->nullable();

            // Lampiran unblock
            $table->string('lampiran_unblock', 300)->nullable();
            $table->string('lampiran_unblock_ori', 300)->nullable();

            // Payment type
            $table->smallInteger('type_customer')->nullable(); // 1=commitment, 2=collateral
            $table->decimal('customer_amount', 22, 2)->default(0);
            $table->date('customer_date')->nullable();

            // Audit
            $table->string('created_by', 255)->nullable();
            $table->timestamp('created_time')->nullable();
            $table->string('lastupdate_by', 255)->nullable();
            $table->timestamp('lastupdate_time')->nullable();

            $table->index(['id_customer', 'disposisi']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_confirmation');
    }
};
