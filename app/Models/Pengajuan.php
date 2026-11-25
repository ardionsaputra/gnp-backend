<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Pengajuan extends Model
{
    use HasFactory;

    protected $table = 'pengajuan';

    protected $primaryKey = 'id_pengajuan';

    protected $fillable = [
        'kode_pengajuan',
        'id_pelanggan',
        'id_unit',
        'tanggal_pengajuan',
        'tanggal_mulai',
        'tanggal_selesai',
        'lokasi_proyek',
        'keterangan',
        'estimasi_harga',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_pengajuan' => 'datetime',
            'tanggal_mulai' => 'date:Y-m-d',
            'tanggal_selesai' => 'date:Y-m-d',
            'estimasi_harga' => 'float',
        ];
    }

    /**
     * Pelanggan yang mengajukan penyewaan.
     */
    public function pelanggan(): BelongsTo
    {
        return $this->belongsTo(Pelanggan::class, 'id_pelanggan', 'id_pelanggan');
    }

    /**
     * Unit alat berat yang diajukan.
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'id_unit', 'id_unit');
    }

    /**
     * Keputusan approval Owner untuk pengajuan ini (maksimal 1).
     */
    public function approval(): HasOne
    {
        return $this->hasOne(Approval::class, 'id_pengajuan', 'id_pengajuan');
    }

    /**
     * Jadwal sewa yang terbentuk setelah pengajuan disetujui (maksimal 1).
     */
    public function jadwal(): HasOne
    {
        return $this->hasOne(Jadwal::class, 'id_pengajuan', 'id_pengajuan');
    }
}
