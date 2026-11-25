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
        Schema::create('unit', function (Blueprint $table) {
            $table->id('id_unit');
            $table->foreignId('id_rekanan')
                ->nullable()
                ->constrained('rekanan', 'id_rekanan')
                ->restrictOnDelete();
            $table->string('nama_unit');
            $table->string('jenis_unit', 50)->index();
            $table->decimal('kapasitas', 10, 2)->index();
            $table->text('spesifikasi')->nullable();
            $table->string('lokasi_unit');
            $table->string('sumber_unit', 50);
            $table->decimal('harga_sewa', 15, 2);
            $table->string('status', 30)->default('tersedia')->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unit');
    }
};
