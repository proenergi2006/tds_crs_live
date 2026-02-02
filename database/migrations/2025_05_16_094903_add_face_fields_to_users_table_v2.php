<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // Pastikan kolom belum ada sebelum menambah
            if (! Schema::hasColumn('users', 'face_image_path')) {
                $table->string('face_image_path')->nullable()->after('remember_token');
            }
            if (! Schema::hasColumn('users', 'face_descriptor')) {
                $table->json('face_descriptor')->nullable()->after('face_image_path');
            }
            if (! Schema::hasColumn('users', 'liveness_passed')) {
                $table->boolean('liveness_passed')->default(false)->after('face_descriptor');
            }
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['face_image_path', 'face_descriptor', 'liveness_passed']);
        });
    }
};
