<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE penawarans DROP CONSTRAINT IF EXISTS penawarans_status_check;");
DB::statement("ALTER TABLE penawarans ADD CONSTRAINT penawarans_status_check 
  CHECK (status IN (
    'draft', 
    'waiting_branch_manager', 
    'approved_bm', 
    'rejected_bm', 
    'approved_om', 
    'rejected_om'
  ))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
