<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $columns = [
        'catatan_verifikasi',
        'catatan_om',
        'approved_at',
        'approved_by',
        'bm_result',
        'om_result',
    ];

    public function up(): void
    {
        Schema::table('penawarans', function (Blueprint $table) {
            foreach ($this->columns as $column) {
                if (Schema::hasColumn('penawarans', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('penawarans', function (Blueprint $table) {
            $table->text('catatan_verifikasi')->nullable();
            $table->text('catatan_om')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->string('approved_by')->nullable();
            $table->string('bm_result')->nullable();
            $table->string('om_result')->nullable();
        });
    }
};
