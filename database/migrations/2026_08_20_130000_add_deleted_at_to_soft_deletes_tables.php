<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Customer/Stock/ReceiveItem model pakai SoftDeletes tapi kolomnya gak pernah ada migration -- ditambah manual dulu di DB, baru ketahuan pas rebuild dari kosong.
return new class extends Migration
{
    public function up(): void
    {
        foreach (['customers', 'stocks', 'receive_items'] as $tableName) {
            if (!Schema::hasColumn($tableName, 'deleted_at')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->softDeletes();
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['customers', 'stocks', 'receive_items'] as $tableName) {
            if (Schema::hasColumn($tableName, 'deleted_at')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropSoftDeletes();
                });
            }
        }
    }
};
