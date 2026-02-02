<?php

// database/migrations/xxxx_xx_xx_xxxxxx_alter_stock_allocations_use_id_prd.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('stock_allocations', function (Blueprint $table) {
            // Tambah kolom baru id_prd (ikuti tipe PK pro_pr_detail)
            $table->unsignedBigInteger('id_prd')->nullable()->after('stock_id');
            $table->index('id_prd', 'stock_allocations_id_prd_idx');
        });

        // Kalau sebelumnya pernah pakai 'pr_detail_id', salin datanya (opsional aman)
        if (Schema::hasColumn('stock_allocations', 'pr_detail_id')) {
            DB::statement('UPDATE stock_allocations SET id_prd = pr_detail_id WHERE id_prd IS NULL');
        }

        // Set NOT NULL setelah migrasi data
        DB::statement('ALTER TABLE stock_allocations ALTER COLUMN id_prd SET NOT NULL');

        // Tambahkan FK ke pro_pr_detail(id_prd)
        Schema::table('stock_allocations', function (Blueprint $table) {
            // Hapus FK lama jika ada
            if (Schema::hasColumn('stock_allocations', 'pr_detail_id')) {
                // Nama constraint lama bisa berbeda; aman-nya try/catch di raw SQL
                try { DB::statement('ALTER TABLE stock_allocations DROP CONSTRAINT IF EXISTS stock_allocations_pr_detail_id_foreign'); } catch (\Throwable $e) {}
            }
            try { DB::statement('ALTER TABLE stock_allocations DROP CONSTRAINT IF EXISTS stock_allocations_id_prd_foreign'); } catch (\Throwable $e) {}

            $table->foreign('id_prd', 'stock_allocations_id_prd_fk')
                  ->references('id_prd')->on('pro_pr_detail')
                  ->onDelete('cascade')->onUpdate('cascade');
        });

        // Opsional: buang kolom lama bila ada
        if (Schema::hasColumn('stock_allocations', 'pr_detail_id')) {
            Schema::table('stock_allocations', function (Blueprint $table) {
                $table->dropColumn('pr_detail_id');
            });
        }
    }

    public function down(): void
    {
        // Revert sederhana: drop FK & kolom id_prd, tambahkan pr_detail_id kembali (opsional)
        Schema::table('stock_allocations', function (Blueprint $table) {
            try { DB::statement('ALTER TABLE stock_allocations DROP CONSTRAINT IF EXISTS stock_allocations_id_prd_fk'); } catch (\Throwable $e) {}
            if (Schema::hasColumn('stock_allocations', 'id_prd')) {
                $table->dropColumn('id_prd');
            }
            $table->unsignedBigInteger('pr_detail_id')->nullable();
        });
    }
};
