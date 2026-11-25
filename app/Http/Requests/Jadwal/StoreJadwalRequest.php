<?php

namespace App\Http\Requests\Jadwal;

use Illuminate\Foundation\Http\FormRequest;

class StoreJadwalRequest extends FormRequest
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
        return [
            'id_unit' => ['required', 'integer', 'exists:unit,id_unit'],
            'id_pengajuan' => ['nullable', 'integer', 'exists:pengajuan,id_pengajuan', 'unique:jadwal,id_pengajuan'],
            'tanggal_mulai' => ['required', 'date'],
            'tanggal_selesai' => ['required', 'date', 'after_or_equal:tanggal_mulai'],
            'status' => ['sometimes', 'string', 'in:terjadwal,berjalan,selesai,dibatalkan'],
        ];
    }
}
