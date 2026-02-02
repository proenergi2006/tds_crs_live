<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDiscountOatToPenawarans extends Migration
{
    public function up()
    {
        Schema::table('penawarans', function (Blueprint $table) {
            $table->decimal('discount', 8, 2)->default(0)->after('total')->comment('Diskon dalam persen');
            $table->decimal('harga_tebus_setelah_diskon', 15, 2)->default(0)->after('discount');
            $table->decimal('total_with_oat', 15, 2)->default(0)->after('harga_tebus_setelah_diskon');
            $table->integer('oat')->default(0)->after('total_with_oat')->comment('OAT Rp per volume');
        });
    }

    public function down()
    {
        Schema::table('penawarans', function (Blueprint $table) {
            $table->dropColumn(['discount', 'harga_tebus_setelah_diskon', 'total_with_oat', 'oat']);
        });
    }
}
