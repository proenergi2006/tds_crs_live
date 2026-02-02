<?php
// database/migrations/2025_01_01_000000_create_penawaran_oas_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('penawaran_oas', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('id_penawaran');
            $t->enum('jenis', ['kapal', 'truck']); // jenis OA
            $t->unsignedBigInteger('id_transportir')->nullable();
            $t->unsignedBigInteger('id_angkut_wilayah')->nullable();
            $t->unsignedBigInteger('id_volume')->nullable();
            $t->decimal('amount', 18, 2)->default(0); // nominal OA per volume (hasil lookup)
            $t->timestamps();

            $t->foreign('id_penawaran')
              ->references('id_penawaran')->on('penawarans')
              ->onDelete('cascade');

            // Satu penawaran maksimal 1 row per jenis (kapal/truck)
            $t->unique(['id_penawaran', 'jenis']);
            $t->index(['id_transportir', 'id_angkut_wilayah', 'id_volume']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penawaran_oas');
    }
};
