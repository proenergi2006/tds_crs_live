<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsActiveAndRoleToUsersTable extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // kolom is_active
            $table->boolean('is_active')->default(true)->after('password');

            // kolom id_role, nullable agar user lama tetap valid
            $table->unsignedBigInteger('id_role')->nullable()->after('is_active');

            // foreign key ke roles.id_role
            $table->foreign('id_role')
                  ->references('id_role')
                  ->on('roles')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['id_role']);
            $table->dropColumn(['id_role', 'is_active']);
        });
    }
}
