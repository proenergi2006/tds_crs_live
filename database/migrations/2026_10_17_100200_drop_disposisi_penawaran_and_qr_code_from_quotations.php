<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const COLUMNS = ['disposisi_penawaran', 'qr_code'];

    public function up(): void
    {
        $missing = array_values(array_filter(
            self::COLUMNS,
            fn (string $column) => ! Schema::hasColumn('quotations', $column)
        ));

        if ($missing !== []) {
            throw new RuntimeException('Cannot drop quotation columns; missing: ' . implode(', ', $missing));
        }

        Schema::table('quotations', function (Blueprint $table) {
            $table->dropColumn(self::COLUMNS);
        });
    }

    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            $table->smallInteger('disposisi_penawaran')->default(0);
            $table->text('qr_code')->nullable();
        });
    }
};
