<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('customer_credit_items');
    }

    public function down(): void
    {
        // Blueprint §6 menetapkan jalur rollback tunggal = restore dump Fase 0; down() yang seolah reversible justru menyesatkan.
        throw new RuntimeException('Irreversible by data — restore customer_credit_items dari dump Fase 0 (lihat Task 1 plan 2026_09_06_120000).');
    }
};
