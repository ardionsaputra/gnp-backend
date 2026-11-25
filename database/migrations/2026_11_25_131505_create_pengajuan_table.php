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
        Schema::create('pengajuan', function (Blueprint $table) {
            $table->id('id_pengajuan');
            $table->string('kode_pengajuan', 32)->unique();
            $table->foreignId('id_pelanggan')
                ->constrained('pelanggan', 'id_pelanggan')
                ->restrictOnDelete();
            $table->foreignId('id_unit')
                ->constrained('unit', 'id_unit')
                ->restrictOnDelete();
            $table->dateTime('tanggal_pengajuan')->index();
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->string('lokasi_proyek');
            $table->text('keterangan')->nullable();
            $table->decimal('estimasi_harga', 15, 2);
            $table->enum('status', [
                'menunggu',
                'diproses',
                'disetujui',
                'ditolak',
                'selesai',
                'dibatalkan',
            ])->default('menunggu')->index();
            $table->timestamps();

            $table->index(['id_unit', 'tanggal_mulai', 'tanggal_selesai'], 'idx_pengajuan_unit_tanggal');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengajuan');
    }
};
