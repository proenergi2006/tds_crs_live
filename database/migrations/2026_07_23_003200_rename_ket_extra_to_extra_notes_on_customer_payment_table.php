<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_payment', function (Blueprint $table) {
            $table->renameColumn('ket_extra', 'extra_notes');
        });
    }

    public function down(): void
    {
        Schema::table('customer_payment', function (Blueprint $table) {
            $table->renameColumn('extra_notes', 'ket_extra');
        });
    }
};
