<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // gap lama: kolom-kolom ini ada di DB live tapi gak pernah tercatat di migration
        // manapun (drift sama seperti produk_hargas) -- guard hasColumn jaga-jaga kalau
        // environment lain (mis. live) udah punya kolomnya duluan
        Schema::table('penawarans', function (Blueprint $table) {
            if (!Schema::hasColumn('penawarans', 'abrasi')) {
                $table->string('abrasi', 100)->nullable();
            }
            if (!Schema::hasColumn('penawarans', 'dp_persen')) {
                $table->string('dp_persen', 10)->nullable();
            }
            if (!Schema::hasColumn('penawarans', 'dp_keterangan')) {
                $table->string('dp_keterangan', 100)->nullable();
            }
            if (!Schema::hasColumn('penawarans', 'repayment_persen')) {
                $table->string('repayment_persen', 10)->nullable();
            }
            if (!Schema::hasColumn('penawarans', 'repayment_hari')) {
                $table->string('repayment_hari', 10)->nullable();
            }
            if (!Schema::hasColumn('penawarans', 'harga_dasar')) {
                $table->decimal('harga_dasar', 15, 2)->nullable();
            }
            if (!Schema::hasColumn('penawarans', 'ppn_harga_dasar')) {
                $table->decimal('ppn_harga_dasar', 15, 2)->nullable();
            }
            if (!Schema::hasColumn('penawarans', 'grand_total_harga_dasar')) {
                $table->decimal('grand_total_harga_dasar', 15, 2)->nullable();
            }
            if (!Schema::hasColumn('penawarans', 'qr_code')) {
                $table->text('qr_code')->nullable();
            }
        });

        if (!Schema::hasColumn('penawarans', 'user_id')) {
            Schema::table('penawarans', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id')->nullable();
                $table->foreign('user_id')->references('id')->on('users')
                    ->onUpdate('cascade')->onDelete('set null');
                $table->index('user_id');
            });
        }

        // CHECK constraint type_pengiriman ketinggalan waktu kolomnya ditambahkan
        // (lihat 2026_08_21_104950_add_type_pengiriman_to_penawarans_table.php) -- live
        // punya constraint ini, samakan
        $constraintExists = DB::selectOne(
            "SELECT 1 FROM pg_constraint WHERE conname = 'penawarans_type_pengiriman_check'"
        );
        if (!$constraintExists) {
            DB::statement(
                "ALTER TABLE penawarans ADD CONSTRAINT penawarans_type_pengiriman_check "
                . "CHECK (type_pengiriman IN ('PROJECT', 'RETAIL'))"
            );
        }
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE penawarans DROP CONSTRAINT IF EXISTS penawarans_type_pengiriman_check');

        if (Schema::hasColumn('penawarans', 'user_id')) {
            Schema::table('penawarans', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            });
        }

        Schema::table('penawarans', function (Blueprint $table) {
            $cols = [
                'abrasi', 'dp_persen', 'dp_keterangan', 'repayment_persen', 'repayment_hari',
                'harga_dasar', 'ppn_harga_dasar', 'grand_total_harga_dasar', 'qr_code',
            ];
            foreach ($cols as $col) {
                if (Schema::hasColumn('penawarans', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
