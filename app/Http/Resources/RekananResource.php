<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RekananResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id_rekanan' => $this->id_rekanan,
            'nama_rekanan' => $this->nama_rekanan,
            'kontak' => $this->kontak,
        ];
    }
}
