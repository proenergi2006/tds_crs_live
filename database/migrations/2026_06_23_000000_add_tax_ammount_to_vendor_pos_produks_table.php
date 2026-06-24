<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('vendor_pos_produks', function (Blueprint $table) {
            if (!Schema::hasColumn('vendor_pos_produks', 'kd_tax')) {
                $table->string('kd_tax')->nullable()->after('jumlah_harga');
                $table->decimal('tax_amount', 22, 4)->default(0)->after('kd_tax');
            }
        });
    }

    public function down(): void
    {
        Schema::table('vendor_pos_produks', function (Blueprint $table) {
            if (Schema::hasColumn('vendor_pos_produks', 'kd_tax')) {
                $table->dropColumn(['kd_tax', 'tax_amount']);
            }
        });
    }
};
