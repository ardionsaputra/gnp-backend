<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Unit extends Model
{
    use HasFactory;

    protected $table = 'unit';

    protected $primaryKey = 'id_unit';

    protected $fillable = [
        'id_rekanan',
        'nama_unit',
        'jenis_unit',
        'kapasitas',
        'spesifikasi',
        'lokasi_unit',
        'sumber_unit',
        'harga_sewa',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'kapasitas' => 'float',
            'harga_sewa' => 'float',
        ];
    }

    /**
     * Rekanan pemilik unit (null jika unit milik internal GNP).
     */
    public function rekanan(): BelongsTo
    {
        return $this->belongsTo(Rekanan::class, 'id_rekanan', 'id_rekanan');
    }

    /**
     * Semua pengajuan sewa untuk unit ini.
     */
    public function pengajuan(): HasMany
    {
        return $this->hasMany(Pengajuan::class, 'id_unit', 'id_unit');
    }

    /**
     * Semua jadwal penggunaan/perawatan untuk unit ini.
     */
    public function jadwal(): HasMany
    {
        return $this->hasMany(Jadwal::class, 'id_unit', 'id_unit');
    }
}
