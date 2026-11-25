<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PemeriksaanResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'pengajuan' => new PengajuanDetailResource($this->resource['pengajuan']),
            'ketersediaan' => $this->resource['ketersediaan'],
            'jumlah_hari' => $this->resource['jumlah_hari'],
            'estimasi_harga' => $this->resource['estimasi_harga'],
        ];
    }
}
