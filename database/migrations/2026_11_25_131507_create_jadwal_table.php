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
        Schema::create('jadwal', function (Blueprint $table) {
            $table->id('id_jadwal');
            $table->foreignId('id_unit')
                ->constrained('unit', 'id_unit')
                ->restrictOnDelete();
            $table->foreignId('id_pengajuan')
                ->nullable()
                ->unique()
                ->constrained('pengajuan', 'id_pengajuan')
                ->restrictOnDelete();
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->string('status', 30)->default('terjadwal')->index();
            $table->timestamps();

            $table->index(['id_unit', 'status', 'tanggal_mulai', 'tanggal_selesai'], 'idx_jadwal_unit_status_tanggal');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jadwal');
    }
};
