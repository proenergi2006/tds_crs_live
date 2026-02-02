<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('sales_colleteral', function (Blueprint $table) {
            $table->foreign('sales_id')
                  ->references('id')->on('sales_confirmation')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('sales_colleteral', function (Blueprint $table) {
            $table->dropForeign(['sales_id']); // akan drop constraint sales_colleteral_sales_id_foreign
        });
    }
};
