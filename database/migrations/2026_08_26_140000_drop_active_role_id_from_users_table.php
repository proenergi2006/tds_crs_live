<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Desain "Acting as" switcher dibatalkan — dashboard jadi tab per role, tidak ada state active role yang perlu disimpan.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['active_role_id']);
            $table->dropColumn('active_role_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('active_role_id')->nullable();
            $table->foreign('active_role_id')->references('id')->on('roles')->onDelete('set null');
        });
    }
};
