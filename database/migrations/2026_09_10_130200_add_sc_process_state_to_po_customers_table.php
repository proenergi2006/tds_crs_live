<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('po_customers', function (Blueprint $table) {
            $table->string('sc_process_state', 20)->nullable();
            $table->index('sc_process_state');
        });
    }

    public function down(): void
    {
        Schema::table('po_customers', function (Blueprint $table) {
            $table->dropColumn('sc_process_state');
        });
    }
};
