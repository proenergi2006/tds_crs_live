<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRolesTable extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            // primary key bernama id_role
            $table->bigIncrements('id_role');

            $table->string('role_name', 100);
            $table->text('role_desc')->nullable();
            $table->boolean('is_active')->default(true);

            // gunakan kolom timestamp biasa, bukan created_at/updated_at
            $table->timestamp('created_time')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamp('lastupdate_time')->nullable();
            $table->string('lastupdate_by')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
}
