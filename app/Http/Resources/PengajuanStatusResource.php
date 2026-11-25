<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource khusus endpoint publik GET /api/pengajuan/status/{kode}.
 * Sengaja TIDAK mengembalikan data pribadi pelanggan (nama, nomor_telepon, email, alamat).
 */
class PengajuanStatusResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'kode_pengajuan' => $this->kode_pengajuan,
            'status' => $this->status,
            'tanggal_pengajuan' => $this->tanggal_pengajuan?->toIso8601String(),
            'tanggal_mulai' => $this->tanggal_mulai?->format('Y-m-d'),
            'tanggal_selesai' => $this->tanggal_selesai?->format('Y-m-d'),
            'lokasi_proyek' => $this->lokasi_proyek,
            'estimasi_harga' => $this->estimasi_harga,
            'unit' => $this->whenLoaded('unit', function () {
                return [
                    'id_unit' => $this->unit->id_unit,
                    'nama_unit' => $this->unit->nama_unit,
                    'jenis_unit' => $this->unit->jenis_unit,
                ];
            }),
        ];
    }
}
