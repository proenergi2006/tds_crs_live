<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // survey_province/survey_regency/survey_address udah pindah ke customer_addresses
        // (address_type=site_address, FK id_lcr). id_wilayah gak pernah punya FK atau
        // relasi Eloquent, orphan dari migration pertama. picustomer digantikan
        // customer_contacts (id_lcr FK, id_contact_type=site_pic). Tabelnya masih 0
        // baris saat ini ditulis, jadi drop langsung aja tanpa migrasi data.
        Schema::table('customer_lcr', function (Blueprint $table) {
            $table->dropColumn(['survey_province', 'survey_regency', 'survey_address', 'id_wilayah', 'picustomer']);
        });
    }

    public function down(): void
    {
        Schema::table('customer_lcr', function (Blueprint $table) {
            $table->unsignedInteger('survey_province')->nullable();
            $table->unsignedInteger('survey_regency')->nullable();
            $table->text('survey_address')->nullable();
            $table->unsignedBigInteger('id_wilayah')->nullable()->index();
            $table->json('picustomer')->nullable();
        });
    }
};
