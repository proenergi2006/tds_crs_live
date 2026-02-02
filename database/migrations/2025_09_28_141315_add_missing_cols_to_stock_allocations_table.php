<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('stock_allocations', function (Blueprint $table) {
            // qty sudah kamu tambahkan sebelumnya; tetap jaga kondisi
            if (!Schema::hasColumn('stock_allocations', 'qty')) {
                $table->decimal('qty', 22, 4)->default(0)->after('id_prd');
            }
            if (!Schema::hasColumn('stock_allocations', 'harga_tebus')) {
                $table->decimal('harga_tebus', 22, 4)->nullable()->after('qty');
            }
            if (!Schema::hasColumn('stock_allocations', 'created_by')) {
                $table->string('created_by', 100)->nullable()->after('harga_tebus');
            }
            // timestamps biasanya sudah ada; kalau belum:
            if (!Schema::hasColumn('stock_allocations', 'created_at')) {
                $table->timestamps();
            }
        });
    }

    public function down(): void
    {
        Schema::table('stock_allocations', function (Blueprint $table) {
            if (Schema::hasColumn('stock_allocations', 'harga_tebus')) {
                $table->dropColumn('harga_tebus');
            }
            // Jangan drop qty/created_by jika sudah dipakai — sesuaikan kebutuhan kamu.
        });
    }
};
