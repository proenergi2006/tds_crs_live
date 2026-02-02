<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddApprovalFieldsToPenawaransTable extends Migration
{
    public function up()
    {
        Schema::table('penawarans', function (Blueprint $table) {
            $table->boolean('penawaran_disetujui')->default(false)->after('jenis_penawaran');

            // Ubah enum status
            $table->dropColumn('status');
        });

        Schema::table('penawarans', function (Blueprint $table) {
            $table->enum('status', [
                'draft',
                'waiting_branch_manager',
                'approved_bm',
                'rejected_bm',
                'approved_om',
                'rejected_om',
            ])->default('draft')->after('penawaran_disetujui');

            $table->timestamp('approved_at')->nullable()->after('status');
            $table->string('approved_by')->nullable()->after('approved_at');
            $table->timestamp('rejected_at')->nullable()->after('approved_by');
            $table->string('rejected_by')->nullable()->after('rejected_at');
            $table->text('rejected_reason')->nullable()->after('rejected_by');
        });
    }

    public function down()
    {
        Schema::table('penawarans', function (Blueprint $table) {
            $table->dropColumn([
                'penawaran_disetujui',
                'status',
                'approved_at',
                'approved_by',
                'rejected_at',
                'rejected_by',
                'rejected_reason',
            ]);
        });
    }
}
