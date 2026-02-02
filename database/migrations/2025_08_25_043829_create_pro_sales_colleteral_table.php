<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sales_colleteral', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->bigInteger('sales_id')->index(); // FK → sales_confirmation.id
            $table->date('date')->nullable();
            $table->decimal('amount', 22, 2)->default(0);
            $table->string('item', 255)->nullable();

            // $table->timestamps(); // aktifkan kalau mau
            // $table->foreign('sales_id')
            //     ->references('id')->on('sales_confirmation')
            //     ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('sales_colleteral', function (Blueprint $table) {
            $table->dropForeign(['sales_id']);
        });
        Schema::dropIfExists('sales_colleteral');
    }
};
