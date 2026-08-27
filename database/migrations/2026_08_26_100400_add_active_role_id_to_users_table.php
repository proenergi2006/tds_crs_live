<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Diisi saat user switch "Acting as"; NULL berarti belum pernah switch dan resolusinya jatuh ke primary_role_id.
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('active_role_id')->nullable();
            $table->foreign('active_role_id')->references('id')->on('roles')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['active_role_id']);
            $table->dropColumn('active_role_id');
        });
    }
};
