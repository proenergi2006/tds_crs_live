<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('penawarans', function (Blueprint $table) {
            $table->unsignedBigInteger('price_period_id')->nullable()->after('sampai_dengan');
            $table->foreign('price_period_id')->references('id')->on('price_periods')->onDelete('restrict');
            $table->index('price_period_id');
        });
    }

    public function down(): void
    {
        Schema::table('penawarans', function (Blueprint $table) {
            $table->dropForeign(['price_period_id']);
            $table->dropIndex('penawarans_price_period_id_index');
            $table->dropColumn('price_period_id');
        });
    }
};
