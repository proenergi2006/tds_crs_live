<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Tabel yatim total setelah customer_contacts.id_contact_type dihapus; tidak ada FK lain yang menunjuk ke sini.
    public function up(): void
    {
        Schema::dropIfExists('customer_contact_types');
    }

    public function down(): void
    {
        Schema::create('customer_contact_types', function (Blueprint $table) {
            $table->bigIncrements('id_contact_type');

            $table->string('code', 50)->unique();
            $table->string('name', 100);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);

            $table->timestamps();
        });
    }
};
