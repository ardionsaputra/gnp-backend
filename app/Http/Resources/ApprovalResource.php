<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApprovalResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id_approval' => $this->id_approval,
            'id_pengajuan' => $this->id_pengajuan,
            'id_user' => $this->id_user,
            'tanggal' => $this->tanggal?->toIso8601String(),
            'keputusan' => $this->keputusan,
            'catatan' => $this->catatan,
            'user' => new UserResource($this->whenLoaded('user')),
        ];
    }
}
