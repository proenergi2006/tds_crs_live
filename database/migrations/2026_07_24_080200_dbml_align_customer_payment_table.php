<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Task DBML-B (customer-kyc-dbml-alignment-migration.md) -- selaraskan
 * `customer_payment` ke DBML artifact `21a36fda` (§12).
 *
 * Mapping numeric->string payment_method/payment_schedule persis dari
 * CustomerVerificationController@saveVerification ($mapPayMethod/
 * $mapSchedule). 0 dipetakan ke NULL (belum pernah diisi), bukan label valid.
 */
return new class extends Migration
{
    public function up(): void
    {
        // DROP NOT NULL dulu sebelum ALTER TYPE -- Postgres memvalidasi
        // constraint NOT NULL terhadap hasil USING (yang menghasilkan NULL
        // untuk payment_method=0/payment_schedule=0) di titik ALTER TYPE.
        DB::statement('ALTER TABLE customer_payment ALTER COLUMN payment_method DROP NOT NULL');
        DB::statement(<<<SQL
            ALTER TABLE customer_payment ALTER COLUMN payment_method TYPE VARCHAR(50) USING CASE payment_method
                WHEN 1 THEN 'Cash'
                WHEN 2 THEN 'Transfer'
                WHEN 3 THEN 'Cheque / Giro'
                WHEN 4 THEN 'Bank Guarantee'
                WHEN 9 THEN 'Other'
                ELSE NULL
            END
        SQL);

        DB::statement('ALTER TABLE customer_payment ALTER COLUMN payment_schedule DROP NOT NULL');
        DB::statement(<<<SQL
            ALTER TABLE customer_payment ALTER COLUMN payment_schedule TYPE VARCHAR(50) USING CASE payment_schedule
                WHEN 1 THEN 'Every Day'
                WHEN 9 THEN 'Other'
                ELSE NULL
            END
        SQL);

        DB::statement('ALTER TABLE customer_payment ALTER COLUMN credit_facility TYPE boolean USING (credit_facility <> 0)');
        DB::statement('ALTER TABLE customer_payment ALTER COLUMN invoice TYPE boolean USING (invoice <> 0)');

        Schema::table('customer_payment', function (Blueprint $table) {
            $table->text('bank_address')->nullable()->change();
            $table->unsignedBigInteger('id_customer')->change();
        });

        Schema::table('customer_payment', function (Blueprint $table) {
            $table->string('payment_method_other', 255)->nullable()->change();
            $table->string('payment_schedule_other', 255)->nullable()->change();
            $table->string('calculate_method', 100)->nullable()->change();
            $table->string('bank_name', 255)->nullable()->change();
            $table->string('account_number', 100)->nullable()->change();
            $table->string('creditor', 255)->nullable()->change();
            $table->string('payment_term', 20)->nullable()->change();
            $table->string('payment_term_basis', 30)->nullable()->change();
        });

        // `currency` dipisah dari batch di atas: 1 row test/dummy data
        // ("Et ut molestiae aspe...", 20 char, lorem-ipsum) melebihi target
        // DBML varchar(10) -- bukan kode ISO 4217 asli, jadi truncate aman
        // (LEFT) daripada gagal migrasi. Tidak ada temuan risiko data nyata
        // di baris ini, lihat laporan gap eksekusi.
        DB::statement('ALTER TABLE customer_payment ALTER COLUMN currency TYPE VARCHAR(10) USING LEFT(currency, 10)');
    }

    public function down(): void
    {
        Schema::table('customer_payment', function (Blueprint $table) {
            $table->string('payment_method_other', 300)->nullable()->change();
            $table->string('payment_schedule_other', 300)->nullable()->change();
            $table->string('calculate_method', 300)->nullable()->change();
            $table->string('bank_name', 300)->nullable()->change();
            $table->string('currency', 300)->nullable()->change();
            $table->string('account_number', 300)->nullable()->change();
            $table->string('creditor', 300)->nullable()->change();
            $table->string('payment_term', 255)->nullable()->change();
            $table->string('payment_term_basis', 255)->nullable()->change();
        });

        Schema::table('customer_payment', function (Blueprint $table) {
            $table->integer('id_customer')->change();
            $table->string('bank_address', 300)->nullable()->change();
        });

        DB::statement('ALTER TABLE customer_payment ALTER COLUMN invoice TYPE smallint USING (CASE WHEN invoice THEN 1 ELSE 0 END)::smallint');
        DB::statement('ALTER TABLE customer_payment ALTER COLUMN credit_facility TYPE integer USING (CASE WHEN credit_facility THEN 1 ELSE 0 END)::integer');

        DB::statement(<<<SQL
            ALTER TABLE customer_payment ALTER COLUMN payment_schedule TYPE smallint USING (CASE payment_schedule
                WHEN 'Every Day' THEN 1
                WHEN 'Other' THEN 9
                ELSE 0
            END)::smallint
        SQL);
        DB::statement('ALTER TABLE customer_payment ALTER COLUMN payment_schedule SET NOT NULL');

        DB::statement(<<<SQL
            ALTER TABLE customer_payment ALTER COLUMN payment_method TYPE smallint USING (CASE payment_method
                WHEN 'Cash' THEN 1
                WHEN 'Transfer' THEN 2
                WHEN 'Cheque / Giro' THEN 3
                WHEN 'Bank Guarantee' THEN 4
                WHEN 'Other' THEN 9
                ELSE 0
            END)::smallint
        SQL);
        DB::statement('ALTER TABLE customer_payment ALTER COLUMN payment_method SET NOT NULL');
    }
};
