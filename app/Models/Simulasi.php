<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Simulasi extends Model
{
    use HasFactory;

    protected $table = 'simulasi';

    protected $primaryKey = 'id_simulasi';

    protected $fillable = [
        'id_pelanggan',
        'jenis_kebutuhan',
        'kapasitas_beban',
        'lokasi_proyek',
        'periode_penggunaan',
        'hasil_rekomendasi',
        'tanggal',
    ];

    protected function casts(): array
    {
        return [
            'kapasitas_beban' => 'float',
            'hasil_rekomendasi' => 'array',
            'tanggal' => 'datetime',
        ];
    }

    /**
     * Pelanggan terkait simulasi (opsional/nullable karena simulasi bersifat publik).
     */
    public function pelanggan(): BelongsTo
    {
        return $this->belongsTo(Pelanggan::class, 'id_pelanggan', 'id_pelanggan');
    }
}
