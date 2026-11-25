<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JadwalResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id_jadwal' => $this->id_jadwal,
            'id_unit' => $this->id_unit,
            'id_pengajuan' => $this->id_pengajuan,
            'tanggal_mulai' => $this->tanggal_mulai?->format('Y-m-d'),
            'tanggal_selesai' => $this->tanggal_selesai?->format('Y-m-d'),
            'status' => $this->status,
            'unit' => new UnitResource($this->whenLoaded('unit')),
            'pengajuan' => new PengajuanResource($this->whenLoaded('pengajuan')),
        ];
    }
}
