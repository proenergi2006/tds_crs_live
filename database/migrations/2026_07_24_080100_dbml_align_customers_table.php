<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Task DBML-A (customer-kyc-dbml-alignment-migration.md) -- selaraskan
 * `customers` ke DBML artifact `21a36fda` (§12).
 *
 * WAJIB jalan SETELAH migration
 * 2026_07_24_080000_dbml_align_customer_verification_review_credit_tables.php
 * (yang menambah kolom credit_limit/credit_limit_diajukan di
 * customer_credit_submissions -- target backfill di bawah).
 *
 * Mapping numeric->string business_type/ownership_type persis dari
 * CustomerVerificationController@saveVerification ($mapTipeBisnis/
 * $mapOwnership, key<->value dibalik di sini karena arah migrasi
 * berkebalikan -- kode sana string->angka untuk kirim ke publik form,
 * di sini angka->string untuk data lama). 0 dipetakan ke NULL (belum
 * pernah diisi), bukan label valid.
 */
return new class extends Migration
{
    public function up(): void
    {
        // DROP NOT NULL/DEFAULT dulu sebelum ALTER TYPE -- Postgres memvalidasi
        // constraint NOT NULL terhadap hasil USING (yang menghasilkan NULL
        // untuk business_type=0/ownership_type=0) di titik ALTER TYPE, jadi
        // constraint lama harus sudah dilepas sebelum konversi jalan.
        DB::statement('ALTER TABLE customers ALTER COLUMN business_type DROP DEFAULT');
        DB::statement('ALTER TABLE customers ALTER COLUMN business_type DROP NOT NULL');
        DB::statement(<<<SQL
            ALTER TABLE customers ALTER COLUMN business_type TYPE VARCHAR(100) USING CASE business_type
                WHEN 1 THEN 'Agriculture & Forestry / Horticulture'
                WHEN 2 THEN 'Business & Information'
                WHEN 3 THEN 'Construction / Utilities / Contracting'
                WHEN 4 THEN 'Education'
                WHEN 5 THEN 'Finance & Insurance'
                WHEN 6 THEN 'Food & hospitality'
                WHEN 7 THEN 'Gaming'
                WHEN 8 THEN 'Health Services'
                WHEN 9 THEN 'Motor Vehicle'
                WHEN 99 THEN 'Other'
                ELSE NULL
            END
        SQL);

        DB::statement('ALTER TABLE customers ALTER COLUMN ownership_type DROP DEFAULT');
        DB::statement('ALTER TABLE customers ALTER COLUMN ownership_type DROP NOT NULL');
        DB::statement(<<<SQL
            ALTER TABLE customers ALTER COLUMN ownership_type TYPE VARCHAR(100) USING CASE ownership_type
                WHEN 1 THEN 'Affiliation'
                WHEN 2 THEN 'National Private'
                WHEN 3 THEN 'Foreign Private'
                WHEN 4 THEN 'Joint Venture'
                WHEN 5 THEN 'BUMN / BUMD'
                WHEN 6 THEN 'Foundation'
                WHEN 7 THEN 'Personal'
                WHEN 99 THEN 'Other'
                ELSE NULL
            END
        SQL);

        DB::statement('ALTER TABLE customers ALTER COLUMN is_link_generated DROP DEFAULT');
        DB::statement('ALTER TABLE customers ALTER COLUMN is_link_generated TYPE boolean USING (is_link_generated <> 0)');
        DB::statement('ALTER TABLE customers ALTER COLUMN is_link_generated SET DEFAULT false');

        // Backfill agregat credit_limit/credit_limit_diajukan customer-level
        // ke customer_credit_submissions (level berbeda dari
        // customer_credit_items yang per-produk) sebelum kolom sumbernya
        // di-drop dari customers. submission_type default 'new_customer'
        // -- ini backfill data lama, bukan submission workflow asli, jadi
        // dipilih value paling netral. created_by/updated_by NULL karena
        // tidak ada user yang bisa diatribusikan ke data lama ini.
        DB::statement(<<<SQL
            INSERT INTO customer_credit_submissions
                (id_customer, submission_type, credit_limit, credit_limit_diajukan, created_by, updated_by, created_at, updated_at)
            SELECT id_customer, 'new_customer', credit_limit, credit_limit_diajukan, NULL, NULL, NOW(), NOW()
            FROM customers
            WHERE credit_limit <> 0 OR credit_limit_diajukan <> 0
        SQL);

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['credit_limit', 'credit_limit_diajukan']);
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->string('postal_code', 20)->nullable()->change();
            $table->string('phone', 50)->nullable()->change();
            $table->string('customer_type', 100)->nullable()->change();
            $table->string('fax', 50)->nullable()->change();
            $table->string('website', 255)->default('')->change();
            $table->string('inco_terms', 50)->nullable()->change();
            $table->string('customer_status', 20)->default('prospect')->change();
            $table->string('business_type_other', 255)->nullable()->change();
            $table->string('ownership_type_other', 255)->nullable()->change();
            $table->string('parent_company', 255)->nullable()->change();
            $table->string('customer_sub_district', 255)->nullable()->change();
            $table->string('customer_village', 255)->nullable()->change();
            $table->string('created_by', 100)->nullable()->change();
            $table->string('updated_by', 100)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('postal_code', 255)->nullable()->change();
            $table->string('phone', 255)->nullable()->change();
            $table->string('customer_type', 255)->nullable()->change();
            $table->string('fax', 255)->nullable()->change();
            $table->string('website', 50)->default('')->change();
            $table->string('inco_terms', 255)->nullable()->change();
            $table->string('customer_status', 255)->default('prospect')->change();
            $table->string('business_type_other', 300)->nullable()->change();
            $table->string('ownership_type_other', 300)->nullable()->change();
            $table->string('parent_company', 300)->nullable()->change();
            $table->string('customer_sub_district', 300)->nullable()->change();
            $table->string('customer_village', 300)->nullable()->change();
            $table->string('created_by', 255)->nullable()->change();
            $table->string('updated_by', 255)->nullable()->change();
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->unsignedBigInteger('credit_limit')->default(0)->after('is_link_generated');
            $table->unsignedBigInteger('credit_limit_diajukan')->default(0)->after('credit_limit');
        });

        // Catatan: data hasil backfill di customer_credit_submissions TIDAK
        // direstore ke sini -- konsisten dengan pola down() data migration
        // lain di project ini (lihat
        // 2026_07_23_000400_migrate_legacy_documents_to_customer_documents_table.php),
        // down() mengembalikan struktur, bukan data.

        DB::statement('ALTER TABLE customers ALTER COLUMN is_link_generated TYPE smallint USING (CASE WHEN is_link_generated THEN 1 ELSE 0 END)::smallint');
        DB::statement('ALTER TABLE customers ALTER COLUMN is_link_generated SET DEFAULT 0');

        DB::statement(<<<SQL
            ALTER TABLE customers ALTER COLUMN ownership_type TYPE smallint USING (CASE ownership_type
                WHEN 'Affiliation' THEN 1
                WHEN 'National Private' THEN 2
                WHEN 'Foreign Private' THEN 3
                WHEN 'Joint Venture' THEN 4
                WHEN 'BUMN / BUMD' THEN 5
                WHEN 'Foundation' THEN 6
                WHEN 'Personal' THEN 7
                WHEN 'Other' THEN 99
                ELSE 0
            END)::smallint
        SQL);
        DB::statement('ALTER TABLE customers ALTER COLUMN ownership_type SET DEFAULT 0');
        DB::statement('ALTER TABLE customers ALTER COLUMN ownership_type SET NOT NULL');

        DB::statement(<<<SQL
            ALTER TABLE customers ALTER COLUMN business_type TYPE smallint USING (CASE business_type
                WHEN 'Agriculture & Forestry / Horticulture' THEN 1
                WHEN 'Business & Information' THEN 2
                WHEN 'Construction / Utilities / Contracting' THEN 3
                WHEN 'Education' THEN 4
                WHEN 'Finance & Insurance' THEN 5
                WHEN 'Food & hospitality' THEN 6
                WHEN 'Gaming' THEN 7
                WHEN 'Health Services' THEN 8
                WHEN 'Motor Vehicle' THEN 9
                WHEN 'Other' THEN 99
                ELSE 0
            END)::smallint
        SQL);
        DB::statement('ALTER TABLE customers ALTER COLUMN business_type SET DEFAULT 0');
        DB::statement('ALTER TABLE customers ALTER COLUMN business_type SET NOT NULL');
    }
};
