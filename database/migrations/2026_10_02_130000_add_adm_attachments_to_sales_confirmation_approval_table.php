<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_confirmation_approval', function (Blueprint $table) {
            $table->jsonb('adm_attachments')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('sales_confirmation_approval', function (Blueprint $table) {
            $table->dropColumn('adm_attachments');
        });
    }
};
