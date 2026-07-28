<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_verifications', function (Blueprint $table) {
            $table->renameColumn('token_verification', 'verification_token');
            $table->renameColumn('legal_tgl_proses', 'legal_processed_at');
            $table->renameColumn('finance_tgl_proses', 'finance_processed_at');
            $table->renameColumn('logistik_data', 'logistics_data');
            $table->renameColumn('logistik_summary', 'logistics_summary');
            $table->renameColumn('logistik_result', 'logistics_result');
            $table->renameColumn('logistik_tgl_proses', 'logistics_processed_at');
            $table->renameColumn('logistik_pic', 'logistics_pic');
            $table->renameColumn('jenis_datanya', 'data_type');
        });
    }

    public function down(): void
    {
        Schema::table('customer_verifications', function (Blueprint $table) {
            $table->renameColumn('verification_token', 'token_verification');
            $table->renameColumn('legal_processed_at', 'legal_tgl_proses');
            $table->renameColumn('finance_processed_at', 'finance_tgl_proses');
            $table->renameColumn('logistics_data', 'logistik_data');
            $table->renameColumn('logistics_summary', 'logistik_summary');
            $table->renameColumn('logistics_result', 'logistik_result');
            $table->renameColumn('logistics_processed_at', 'logistik_tgl_proses');
            $table->renameColumn('logistics_pic', 'logistik_pic');
            $table->renameColumn('data_type', 'jenis_datanya');
        });
    }
};
