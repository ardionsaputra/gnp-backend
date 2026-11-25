<?php

namespace App\Http\Requests\Jadwal;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateJadwalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $jadwalId = $this->route('id');

        return [
            'id_unit' => ['sometimes', 'required', 'integer', 'exists:unit,id_unit'],
            'id_pengajuan' => [
                'nullable',
                'integer',
                'exists:pengajuan,id_pengajuan',
                Rule::unique('jadwal', 'id_pengajuan')->ignore($jadwalId, 'id_jadwal'),
            ],
            'tanggal_mulai' => ['sometimes', 'required', 'date'],
            'tanggal_selesai' => ['sometimes', 'required', 'date', 'after_or_equal:tanggal_mulai'],
            'status' => ['sometimes', 'required', 'string', 'in:terjadwal,berjalan,selesai,dibatalkan'],
        ];
    }
}
