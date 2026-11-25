<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rekanan extends Model
{
    use HasFactory;

    protected $table = 'rekanan';

    protected $primaryKey = 'id_rekanan';

    protected $fillable = [
        'nama_rekanan',
        'kontak',
    ];

    /**
     * Daftar unit alat berat milik rekanan ini.
     */
    public function units(): HasMany
    {
        return $this->hasMany(Unit::class, 'id_rekanan', 'id_rekanan');
    }
}
