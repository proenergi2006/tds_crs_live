<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Penanda role posisi/default user; otorisasi dibaca dari model_has_roles, bukan dari kolom ini.
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('id_role', 'primary_role_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreign('primary_role_id')->references('id')->on('roles')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['primary_role_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('primary_role_id', 'id_role');
        });
    }
};
