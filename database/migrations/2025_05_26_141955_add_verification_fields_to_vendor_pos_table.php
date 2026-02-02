<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('vendor_pos', function (Blueprint $table) {
            // CFO fields
            $table->tinyInteger('cfo_result')->nullable()->after('disposisi_po')->comment('1=approve,2=reject');
            $table->text('cfo_summary')->nullable()->after('cfo_result');
            $table->timestamp('cfo_tgl')->nullable()->after('cfo_summary');
            $table->string('cfo_signature')->nullable()->after('cfo_tgl');

            // CEO fields
            $table->tinyInteger('ceo_result')->nullable()->after('cfo_signature')->comment('1=approve,2=reject');
            $table->text('ceo_summary')->nullable()->after('ceo_result');
            $table->timestamp('ceo_tgl')->nullable()->after('ceo_summary');
            $table->string('ceo_signature')->nullable()->after('ceo_tgl');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('vendor_pos', function (Blueprint $table) {
            $table->dropColumn([
                'cfo_result',
                'cfo_summary',
                'cfo_tgl',
                'cfo_signature',
                'ceo_result',
                'ceo_summary',
                'ceo_tgl',
                'ceo_signature',
            ]);
        });
    }
};
