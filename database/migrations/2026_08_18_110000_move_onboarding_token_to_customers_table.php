<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Token cuma punya satu slot aktif per customer (regenerate = overwrite), jadi dipindah ke customers langsung.
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('is_link_generated');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->string('onboarding_token')->nullable()->unique();
            $table->timestamp('token_expired_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['onboarding_token', 'token_expired_at']);
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->boolean('is_link_generated')->default(false);
        });
    }
};
