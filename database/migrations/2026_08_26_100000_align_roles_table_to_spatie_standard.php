<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // roles disamakan ke skema standar Spatie; guard_name wajib ada meski project ini cuma pakai satu guard.
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['id_role']);
        });

        Schema::table('role_has_permissions', function (Blueprint $table) {
            $table->dropForeign(['id_role']);
        });

        Schema::table('approval_template_steps', function (Blueprint $table) {
            $table->dropForeign(['id_role']);
        });

        Schema::table('roles', function (Blueprint $table) {
            $table->renameColumn('id_role', 'id');
            $table->renameColumn('role_name', 'name');
            $table->renameColumn('created_time', 'created_at');
            $table->renameColumn('lastupdate_time', 'updated_at');
        });

        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn(['created_by', 'lastupdate_by']);
            $table->string('guard_name')->default('web');
            $table->string('name', 255)->change();
        });

        Schema::table('roles', function (Blueprint $table) {
            $table->unique(['name', 'guard_name'], 'roles_name_guard_name_unique');
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropUnique('roles_name_guard_name_unique');
        });

        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn('guard_name');
            $table->string('name', 100)->change();
            $table->string('created_by')->nullable();
            $table->string('lastupdate_by')->nullable();
        });

        Schema::table('roles', function (Blueprint $table) {
            $table->renameColumn('id', 'id_role');
            $table->renameColumn('name', 'role_name');
            $table->renameColumn('created_at', 'created_time');
            $table->renameColumn('updated_at', 'lastupdate_time');
        });

        Schema::table('approval_template_steps', function (Blueprint $table) {
            $table->foreign('id_role')->references('id_role')->on('roles')->onDelete('restrict');
        });

        Schema::table('role_has_permissions', function (Blueprint $table) {
            $table->foreign('id_role')->references('id_role')->on('roles')->onDelete('cascade');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreign('id_role')->references('id_role')->on('roles')->onDelete('set null');
        });
    }
};
