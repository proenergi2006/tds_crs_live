<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transport_tariffs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transporter_id')->constrained('transporters');
            $table->string('transport_type', 20);
            $table->foreignId('transport_area_id')->constrained('transport_areas');
            $table->foreignId('volume_id')->constrained('volumes');
            $table->decimal('rate', 15, 2);
            $table->text('note')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
        });

        DB::statement('CREATE UNIQUE INDEX transport_tariffs_active_combination_unique ON transport_tariffs (transporter_id, transport_type, transport_area_id, volume_id) WHERE is_active = true');
    }

    public function down(): void
    {
        Schema::dropIfExists('transport_tariffs');
    }
};
