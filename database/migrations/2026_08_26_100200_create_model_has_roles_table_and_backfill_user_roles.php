<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Multi-role sebenarnya disimpan di sini; kolom role di users tinggal metadata, bukan sumber otorisasi.
    public function up(): void
    {
        Schema::create('model_has_roles', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');

            $table->index(['model_id', 'model_type'], 'model_has_roles_model_id_model_type_index');

            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');

            $table->primary(['role_id', 'model_id', 'model_type'], 'model_has_roles_role_model_type_primary');
        });

        // Sengaja baca users.id_role: kolom itu baru di-rename oleh migration berikutnya.
        $users = DB::table('users')->whereNotNull('id_role')->orderBy('id')->get(['id', 'id_role']);

        $rows = [];
        foreach ($users as $user) {
            $rows[] = [
                'role_id' => $user->id_role,
                'model_type' => 'App\Models\User',
                'model_id' => $user->id,
            ];
        }

        if (!empty($rows)) {
            DB::table('model_has_roles')->insert($rows);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('model_has_roles');
    }
};
