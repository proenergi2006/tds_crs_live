<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('customer_review_attchment', function (Blueprint $t) {
            $t->unsignedInteger('id_review');
            $t->unsignedInteger('id_verification');
            $t->unsignedInteger('no_urut');

            $t->string('review_attach', 500)->nullable();     // path (storage)
            $t->string('review_attach_ori', 500)->nullable(); // original filename

            $t->primary(['id_review','id_verification','no_urut']);
            $t->foreign('id_review')->references('id_review')->on('customer_review')
              ->cascadeOnUpdate()->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_review_attchment');
    }
};
