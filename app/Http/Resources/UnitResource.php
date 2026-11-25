<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UnitResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id_unit' => $this->id_unit,
            'id_rekanan' => $this->id_rekanan,
            'nama_unit' => $this->nama_unit,
            'jenis_unit' => $this->jenis_unit,
            'kapasitas' => $this->kapasitas,
            'spesifikasi' => $this->spesifikasi,
            'lokasi_unit' => $this->lokasi_unit,
            'sumber_unit' => $this->sumber_unit,
            'harga_sewa' => $this->harga_sewa,
            'status' => $this->status,
            'rekanan' => $this->whenLoaded('rekanan', function () {
                return $this->rekanan ? [
                    'id_rekanan' => $this->rekanan->id_rekanan,
                    'nama_rekanan' => $this->rekanan->nama_rekanan,
                ] : null;
            }),
        ];
    }
}
