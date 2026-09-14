<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('po_customer_unblock_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_poc');
            $table->text('reason')->nullable();
            $table->jsonb('attachments')->nullable();
            $table->string('status', 20)->default('in_progress');
            $table->unsignedBigInteger('requested_by')->nullable();
            $table->timestamps();

            $table->index('id_poc');
            $table->index('status');

            $table->foreign('id_poc')->references('id_poc')->on('po_customers')->cascadeOnDelete();
            $table->foreign('requested_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('po_customer_unblock_requests');
    }
};
