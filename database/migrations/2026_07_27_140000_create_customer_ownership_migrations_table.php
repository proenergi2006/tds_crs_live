<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_ownership_migrations', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('id_customer');

            $table->unsignedBigInteger('from_user_id')->nullable();
            $table->unsignedBigInteger('to_user_id');
            $table->unsignedBigInteger('migrated_by')->nullable();

            $table->dateTime('migrated_at');
            $table->text('notes')->nullable();

            $table->foreign('id_customer')
                ->references('id_customer')->on('customers')
                ->onDelete('cascade');

            $table->foreign('from_user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('to_user_id')->references('id')->on('users');
            $table->foreign('migrated_by')->references('id')->on('users')->onDelete('set null');

            $table->index(['id_customer', 'migrated_at']);
        });
    }

    public function down(): void
    {
        Schema::table('customer_ownership_migrations', function (Blueprint $table) {
            $table->dropForeign(['id_customer']);
            $table->dropForeign(['from_user_id']);
            $table->dropForeign(['to_user_id']);
            $table->dropForeign(['migrated_by']);
        });

        Schema::dropIfExists('customer_ownership_migrations');
    }
};
