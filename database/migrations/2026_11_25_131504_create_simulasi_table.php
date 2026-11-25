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
        Schema::create('simulasi', function (Blueprint $table) {
            $table->id('id_simulasi');
            $table->foreignId('id_pelanggan')
                ->nullable()
                ->constrained('pelanggan', 'id_pelanggan')
                ->nullOnDelete();
            $table->string('jenis_kebutuhan');
            $table->decimal('kapasitas_beban', 10, 2);
            $table->string('lokasi_proyek');
            $table->string('periode_penggunaan');
            $table->json('hasil_rekomendasi')->nullable();
            $table->dateTime('tanggal')->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('simulasi');
    }
};
