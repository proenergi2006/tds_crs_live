<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('id_role');

            $table->foreign('permission_id')
                ->references('id')
                ->on('permissions')
                ->onDelete('cascade');

            // FK ke id_role (bukan ke roles.id — Spatie default) karena tabel roles
            // existing pakai PK id_role, bukan id.
            $table->foreign('id_role')
                ->references('id_role')
                ->on('roles')
                ->onDelete('cascade');

            $table->primary(['permission_id', 'id_role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_has_permissions');
    }
};
