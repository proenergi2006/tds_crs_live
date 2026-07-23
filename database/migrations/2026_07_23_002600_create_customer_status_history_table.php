<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_status_history', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('id_customer');

            // String biasa (bukan DB-level enum), di-cast ke PHP backed enum
            // App\Enums\CustomerStatus di model.
            $table->string('status');

            $table->dateTime('changed_at');
            $table->unsignedBigInteger('changed_by')->nullable();

            // String biasa (bukan DB-level enum), di-cast ke PHP backed enum
            // App\Enums\CustomerStatusTriggerSource di model.
            $table->string('trigger_source');

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->foreign('id_customer')
                ->references('id_customer')->on('customers')
                ->onDelete('cascade');

            $table->foreign('changed_by')->references('id')->on('users')->onDelete('set null');

            $table->index(['id_customer', 'changed_at']);
        });
    }

    public function down(): void
    {
        Schema::table('customer_status_history', function (Blueprint $table) {
            $table->dropForeign(['id_customer']);
            $table->dropForeign(['changed_by']);
        });

        Schema::dropIfExists('customer_status_history');
    }
};
