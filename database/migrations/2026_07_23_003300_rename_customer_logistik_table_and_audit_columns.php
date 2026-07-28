<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('customer_logistik', 'customer_logistic_claims');

        Schema::table('customer_logistic_claims', function (Blueprint $table) {
            $table->dropColumn(['created_ip', 'lastupdate_ip']);
        });

        Schema::table('customer_logistic_claims', function (Blueprint $table) {
            $table->renameColumn('created_time', 'created_at');
            $table->renameColumn('lastupdate_time', 'updated_at');
            $table->renameColumn('lastupdate_by', 'updated_by');
        });
    }

    public function down(): void
    {
        Schema::rename('customer_logistic_claims', 'customer_logistik');

        Schema::table('customer_logistik', function (Blueprint $table) {
            $table->renameColumn('created_at', 'created_time');
            $table->renameColumn('updated_at', 'lastupdate_time');
            $table->renameColumn('updated_by', 'lastupdate_by');
        });

        Schema::table('customer_logistik', function (Blueprint $table) {
            $table->string('created_ip', 45)->nullable();
            $table->string('lastupdate_ip', 45)->nullable();
        });
    }
};
