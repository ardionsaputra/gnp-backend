<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SimulasiResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id_simulasi' => $this->id_simulasi,
            'id_pelanggan' => $this->id_pelanggan,
            'jenis_kebutuhan' => $this->jenis_kebutuhan,
            'kapasitas_beban' => $this->kapasitas_beban,
            'lokasi_proyek' => $this->lokasi_proyek,
            'periode_penggunaan' => $this->periode_penggunaan,
            'hasil_rekomendasi' => $this->hasil_rekomendasi,
            'tanggal' => $this->tanggal?->toIso8601String(),
        ];
    }
}
