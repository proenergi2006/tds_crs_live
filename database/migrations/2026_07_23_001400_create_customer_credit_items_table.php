<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_credit_items', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('id_submission');
            $table->unsignedBigInteger('id_produk');

            $table->decimal('volume', 22, 4)->nullable();
            $table->string('unit')->nullable();

            $table->decimal('existing_limit', 22, 4)->nullable();
            $table->decimal('actual_payment', 22, 4)->nullable();
            $table->string('guarantee')->nullable();

            $table->decimal('credit_limit_request', 22, 4)->nullable();
            $table->decimal('credit_limit_approval', 22, 4)->nullable();

            $table->integer('top_request')->nullable();
            $table->integer('top_approval')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->foreign('id_submission')
                ->references('id')->on('customer_credit_submissions')
                ->onDelete('cascade');

            $table->foreign('id_produk')
                ->references('id_produk')->on('produks')
                ->onDelete('restrict');

            $table->index(['id_submission', 'id_produk']);
        });
    }

    public function down(): void
    {
        Schema::table('customer_credit_items', function (Blueprint $table) {
            $table->dropForeign(['id_submission']);
            $table->dropForeign(['id_produk']);
        });

        Schema::dropIfExists('customer_credit_items');
    }
};
