<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PengajuanDetailResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id_pengajuan' => $this->id_pengajuan,
            'kode_pengajuan' => $this->kode_pengajuan,
            'id_pelanggan' => $this->id_pelanggan,
            'id_unit' => $this->id_unit,
            'tanggal_pengajuan' => $this->tanggal_pengajuan?->toIso8601String(),
            'tanggal_mulai' => $this->tanggal_mulai?->format('Y-m-d'),
            'tanggal_selesai' => $this->tanggal_selesai?->format('Y-m-d'),
            'lokasi_proyek' => $this->lokasi_proyek,
            'keterangan' => $this->keterangan,
            'estimasi_harga' => $this->estimasi_harga,
            'status' => $this->status,
            'pelanggan' => new PelangganResource($this->whenLoaded('pelanggan')),
            'unit' => new UnitResource($this->whenLoaded('unit')),
            'approval' => new ApprovalResource($this->whenLoaded('approval')),
            'jadwal' => new JadwalResource($this->whenLoaded('jadwal')),
        ];
    }
}
