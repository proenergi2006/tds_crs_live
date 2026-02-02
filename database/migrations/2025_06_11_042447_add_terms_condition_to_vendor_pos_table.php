<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTermsConditionToVendorPosTable extends Migration
{
    public function up()
    {
        Schema::table('vendor_pos', function (Blueprint $table) {
            $table->text('terms_condition')->nullable()->after('terms_day');
        });
    }

    public function down()
    {
        Schema::table('vendor_pos', function (Blueprint $table) {
            $table->dropColumn('terms_condition');
        });
    }
}
