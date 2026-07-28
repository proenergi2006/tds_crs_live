<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('customer_contacts', 'customer_contacts_legacy');
    }

    public function down(): void
    {
        Schema::rename('customer_contacts_legacy', 'customer_contacts');
    }
};
