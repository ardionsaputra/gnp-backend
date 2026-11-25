<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pelanggan extends Model
{
    use HasFactory;

    protected $table = 'pelanggan';

    protected $primaryKey = 'id_pelanggan';

    protected $fillable = [
        'nama',
        'nomor_telepon',
        'email',
        'alamat',
    ];

    /**
     * Riwayat simulasi kebutuhan yang pernah ditautkan ke pelanggan ini.
     */
    public function simulasi(): HasMany
    {
        return $this->hasMany(Simulasi::class, 'id_pelanggan', 'id_pelanggan');
    }

    /**
     * Daftar pengajuan penyewaan yang diajukan pelanggan ini.
     */
    public function pengajuan(): HasMany
    {
        return $this->hasMany(Pengajuan::class, 'id_pelanggan', 'id_pelanggan');
    }
}
