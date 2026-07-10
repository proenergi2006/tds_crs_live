<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_approval_steps', function (Blueprint $table) {
            // PK standar auto-increment `id` — mengikuti konvensi modul baru non-legacy
            // (mis. stock_allocations, sales_confirmation_approval), bukan custom id_x,
            // karena tabel ini bukan tabel "utama" domain (child/log record).
            $table->id();

            $table->unsignedBigInteger('id_approval');
            $table->unsignedBigInteger('id_template_step');

            // Snapshot step_order saat baris ini dibuat — bukan live join ke
            // approval_template_steps, supaya histori tidak berubah kalau urutan step
            // template diubah di kemudian hari.
            $table->unsignedInteger('step_order');

            // Status: string biasa (bukan DB-level enum), di-cast ke PHP backed enum di model.
            $table->string('status')->default('pending');

            $table->unsignedBigInteger('actor_id')->nullable();
            $table->timestamp('acted_at')->nullable();
            $table->text('decision_note')->nullable();

            $table->timestamps();

            $table->foreign('id_approval')
                ->references('id_approval')->on('document_approvals')
                ->onDelete('cascade');

            $table->foreign('id_template_step')
                ->references('id_step')->on('approval_template_steps')
                ->onDelete('restrict');

            $table->foreign('actor_id')
                ->references('id')->on('users')
                ->onDelete('set null');

            $table->unique(['id_approval', 'step_order'], 'document_approval_steps_approval_order_uq');
        });
    }

    public function down(): void
    {
        Schema::table('document_approval_steps', function (Blueprint $table) {
            $table->dropForeign(['id_approval']);
            $table->dropForeign(['id_template_step']);
            $table->dropForeign(['actor_id']);
        });

        Schema::dropIfExists('document_approval_steps');
    }
};
