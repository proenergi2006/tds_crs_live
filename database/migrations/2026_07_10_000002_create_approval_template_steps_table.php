<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_template_steps', function (Blueprint $table) {
            $table->id('id_step');

            $table->unsignedBigInteger('id_template');
            $table->unsignedInteger('step_order');
            $table->string('step_name');

            // FK ke roles.id_role (bukan roles.id — tabel roles pakai PK custom id_role)
            $table->unsignedBigInteger('id_role');

            $table->timestamps();

            $table->foreign('id_template')
                ->references('id_template')->on('approval_templates')
                ->onDelete('cascade');

            $table->foreign('id_role')
                ->references('id_role')->on('roles')
                ->onDelete('restrict');

            $table->unique(['id_template', 'step_order'], 'approval_template_steps_template_order_uq');
        });
    }

    public function down(): void
    {
        Schema::table('approval_template_steps', function (Blueprint $table) {
            $table->dropForeign(['id_template']);
            $table->dropForeign(['id_role']);
        });

        Schema::dropIfExists('approval_template_steps');
    }
};
