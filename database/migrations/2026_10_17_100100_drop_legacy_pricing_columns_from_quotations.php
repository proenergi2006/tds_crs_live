<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const COLUMNS = [
        'harga_dasar',
        'ppn_harga_dasar',
        'grand_total_harga_dasar',
        'biaya_kirim',
        'diskon',
        'perhitungan',
        'fax',
        'penawaran_disetujui',
        'rejected_at',
        'rejected_by',
        'rejected_reason',
        'jenis_penawaran',
        'subtotal',
        'ppn11',
        'total',
        'total_with_oat',
        'harga_tebus_setelah_diskon',
        'discount',
        'oat',
    ];

    public function up(): void
    {
        $missing = array_values(array_filter(
            self::COLUMNS,
            fn (string $column) => ! Schema::hasColumn('quotations', $column)
        ));

        if ($missing !== []) {
            throw new RuntimeException('Cannot drop legacy quotation columns; missing: ' . implode(', ', $missing));
        }

        Schema::table('quotations', function (Blueprint $table) {
            $table->dropColumn(self::COLUMNS);
        });
    }

    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            $table->decimal('harga_dasar', 15, 2)->nullable();
            $table->decimal('ppn_harga_dasar', 15, 2)->nullable();
            $table->decimal('grand_total_harga_dasar', 15, 2)->nullable();
            $table->decimal('biaya_kirim', 15, 2)->default(0);
            $table->decimal('diskon', 5, 2)->default(0);
            $table->string('perhitungan', 255)->nullable();
            $table->string('fax', 255)->nullable();
            $table->boolean('penawaran_disetujui')->default(false);
            $table->timestamp('rejected_at')->nullable();
            $table->string('rejected_by', 255)->nullable();
            $table->text('rejected_reason')->nullable();
            $table->string('jenis_penawaran', 50)->nullable();
            $table->decimal('subtotal', 20, 2)->default(0);
            $table->decimal('ppn11', 20, 2)->default(0);
            $table->decimal('total', 20, 2)->default(0);
            $table->decimal('total_with_oat', 15, 2)->default(0);
            $table->decimal('harga_tebus_setelah_diskon', 15, 2)->default(0);
            $table->decimal('discount', 8, 2)->default(0);
            $table->integer('oat')->default(0);
        });
    }
};
