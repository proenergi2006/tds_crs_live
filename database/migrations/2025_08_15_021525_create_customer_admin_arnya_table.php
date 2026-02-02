<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_admin_arnya', function (Blueprint $table) {
            // match legacy options
            $table->engine = 'InnoDB';
            $table->charset = 'latin1';
            $table->collation = 'latin1_swedish_ci';

            // PK (INT AUTO_INCREMENT)
            // pakai signed agar mirip dump; kalau mau unsigned gunakan ->increments()
            $table->integer('id_arnya')->autoIncrement();
            $table->integer('id_customer'); // samakan signed/unsigned dgn pro_customer.id_customer

            // nominal
            $table->decimal('not_yet',     22, 2)->default(0);
            $table->decimal('ov_up_07',    22, 2)->default(0);
            $table->decimal('ov_under_30', 22, 2)->default(0);
            $table->decimal('ov_under_60', 22, 2)->default(0);
            $table->decimal('ov_under_90', 22, 2)->default(0);
            $table->decimal('ov_up_90',    22, 2)->default(0);

            // index & FK
            $table->index('id_customer', 'pro_customer_admin_arnya_idx1');
            $table->foreign('id_customer', 'pro_customer_admin_arnya_fk1')
                ->references('id_customer')->on('customers')
                ->cascadeOnUpdate(); // dump tdk mendefinisikan ON DELETE
        });

        // OPTIONAL: kalau mau samakan AUTO_INCREMENT awal seperti dump (10140)
        // DB::statement('ALTER TABLE customer_admin_arnya AUTO_INCREMENT = 10140');
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_admin_arnya');
    }
};
