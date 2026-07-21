<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_approvals', function (Blueprint $table) {
            $table->id('id_approval');

            $table->unsignedBigInteger('id_template');

            // Polymorphic target (approvable_type + approvable_id), lookup diindex manual
            // di bawah (bukan $table->morphs() karena nama kolom sudah ditentukan eksplisit
            // di spec dan agar konsisten dengan penamaan approvable_type/approvable_id).
            $table->string('approvable_type');
            $table->unsignedBigInteger('approvable_id');

            // Status: string biasa (bukan DB-level enum), di-cast ke PHP backed enum di model.
            $table->string('status')->default('in_progress');

            $table->unsignedInteger('current_step_order')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

            $table->foreign('id_template')
                ->references('id_template')->on('approval_templates')
                ->onDelete('restrict');

            $table->index(['approvable_type', 'approvable_id'], 'document_approvals_approvable_idx');
        });
    }

    public function down(): void
    {
        Schema::table('document_approvals', function (Blueprint $table) {
            $table->dropForeign(['id_template']);
        });

        Schema::dropIfExists('document_approvals');
    }
};
