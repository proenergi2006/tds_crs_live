<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_verifications', function (Blueprint $table) {
            $table->dropColumn([
                'sm_summary',
                'sm_result',
                'sm_tgl_proses',
                'sm_pic',

                'om_summary',
                'om_result',
                'om_tgl_proses',
                'om_pic',

                'cfo_summary',
                'cfo_result',
                'cfo_tgl_proses',
                'cfo_pic',

                'ceo_summary',
                'ceo_result',
                'ceo_tgl_proses',
                'ceo_pic',

                'disposisi_result',
                'is_approved',
                'role_approve',
                'tanggal_approved',
            ]);
        });

        Schema::table('customer_verifications', function (Blueprint $table) {
            $table->renameColumn('is_evaluated', 'is_submitted');
            $table->renameColumn('is_reviewed', 'is_forwarded');
        });
    }

    public function down(): void
    {
        Schema::table('customer_verifications', function (Blueprint $table) {
            $table->renameColumn('is_submitted', 'is_evaluated');
            $table->renameColumn('is_forwarded', 'is_reviewed');
        });

        Schema::table('customer_verifications', function (Blueprint $table) {
            $table->tinyInteger('sm_result')->default(0);
            $table->text('sm_summary')->nullable();
            $table->dateTime('sm_tgl_proses')->nullable();
            $table->string('sm_pic', 50)->nullable();

            $table->tinyInteger('om_result')->default(0);
            $table->text('om_summary')->nullable();
            $table->dateTime('om_tgl_proses')->nullable();
            $table->string('om_pic', 50)->nullable();

            $table->tinyInteger('cfo_result')->default(0);
            $table->text('cfo_summary')->nullable();
            $table->dateTime('cfo_tgl_proses')->nullable();
            $table->string('cfo_pic', 50)->nullable();

            $table->tinyInteger('ceo_result')->default(0);
            $table->text('ceo_summary')->nullable();
            $table->dateTime('ceo_tgl_proses')->nullable();
            $table->string('ceo_pic', 50)->nullable();

            $table->tinyInteger('disposisi_result')->default(0);
            $table->tinyInteger('is_approved')->default(0);
            $table->integer('role_approve')->nullable();
            $table->dateTime('tanggal_approved')->nullable();
        });
    }
};
