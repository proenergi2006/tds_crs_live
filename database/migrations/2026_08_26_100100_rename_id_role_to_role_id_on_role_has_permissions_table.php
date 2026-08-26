<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // balikin nama kolom pivot ke default package Spatie, jadi gak perlu override role_pivot_key lagi.
    public function up(): void
    {
        Schema::table('role_has_permissions', function (Blueprint $table) {
            $table->renameColumn('id_role', 'role_id');
        });

        Schema::table('role_has_permissions', function (Blueprint $table) {
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('role_has_permissions', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
        });

        Schema::table('role_has_permissions', function (Blueprint $table) {
            $table->renameColumn('role_id', 'id_role');
        });
    }
};
