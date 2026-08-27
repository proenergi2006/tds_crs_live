<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // FK ini cuma mengikuti PK roles yang berpindah nama; kolom id_role sengaja tidak ikut di-rename.
    public function up(): void
    {
        Schema::table('approval_template_steps', function (Blueprint $table) {
            $table->foreign('id_role')->references('id')->on('roles')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::table('approval_template_steps', function (Blueprint $table) {
            $table->dropForeign(['id_role']);
        });
    }
};
