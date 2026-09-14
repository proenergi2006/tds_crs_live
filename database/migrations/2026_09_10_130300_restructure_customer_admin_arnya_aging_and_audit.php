<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $duplicates = DB::table('customer_admin_arnya')
            ->select('id_customer')
            ->groupBy('id_customer')
            ->havingRaw('COUNT(*) > 1')
            ->count();

        if ($duplicates > 0) {
            throw new RuntimeException("Migration dibatalkan: {$duplicates} id_customer duplikat di customer_admin_arnya — UNIQUE(id_customer) tidak bisa dipasang tanpa dedup manual.");
        }

        Schema::table('customer_admin_arnya', function (Blueprint $table) {
            $table->decimal('overdue_1_30', 22, 2)->default(0);
        });

        DB::statement('UPDATE customer_admin_arnya SET overdue_1_30 = ov_up_07 + ov_under_30');

        DB::statement('ALTER TABLE customer_admin_arnya RENAME COLUMN not_yet TO outstanding_current');
        DB::statement('ALTER TABLE customer_admin_arnya RENAME COLUMN ov_under_60 TO overdue_31_60');
        DB::statement('ALTER TABLE customer_admin_arnya RENAME COLUMN ov_under_90 TO overdue_61_90');
        DB::statement('ALTER TABLE customer_admin_arnya RENAME COLUMN ov_up_90 TO overdue_90_plus');

        Schema::table('customer_admin_arnya', function (Blueprint $table) {
            $table->dropColumn(['ov_up_07', 'ov_under_30']);
        });

        Schema::table('customer_admin_arnya', function (Blueprint $table) {
            $table->timestamps();
            $table->unsignedBigInteger('updated_by')->nullable();
        });

        DB::statement('UPDATE customer_admin_arnya SET created_at = NOW(), updated_at = NOW()');

        Schema::table('customer_admin_arnya', function (Blueprint $table) {
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('customer_admin_arnya', function (Blueprint $table) {
            $table->unique('id_customer', 'customer_admin_arnya_id_customer_uq');
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Irreversible by data — split ov_up_07 (1-7 hari) vs ov_under_30 (8-30 hari) sudah hilang setelah digabung ke overdue_1_30. Rollback = restore dump customer_admin_arnya yang diambil sebelum migrate.');
    }
};
