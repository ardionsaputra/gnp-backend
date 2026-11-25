<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PelangganResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id_pelanggan' => $this->id_pelanggan,
            'nama' => $this->nama,
            'nomor_telepon' => $this->nomor_telepon,
            'email' => $this->email,
            'alamat' => $this->alamat,
        ];
    }
}
