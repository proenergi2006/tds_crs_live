<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('penawarans', function (Blueprint $table) {
            $table->decimal('oa_kapal', 12, 2)->nullable()->after('oat');
            $table->decimal('oa_truck', 12, 2)->nullable()->after('oa_kapal');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('penawarans', function (Blueprint $table) {
            //
        });
    }
};
