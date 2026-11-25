<?php

namespace App\Http\Requests\Unit;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUnitRequest extends FormRequest
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
            'id_rekanan' => ['nullable', 'integer', 'exists:rekanan,id_rekanan'],
            'nama_unit' => ['sometimes', 'required', 'string', 'max:150'],
            'jenis_unit' => ['sometimes', 'required', 'string', 'max:50'],
            'kapasitas' => ['sometimes', 'required', 'numeric', 'gt:0'],
            'spesifikasi' => ['sometimes', 'required', 'string', 'max:1000'],
            'lokasi_unit' => ['sometimes', 'required', 'string', 'max:150'],
            'sumber_unit' => ['sometimes', 'required', 'string', 'in:internal,rekanan'],
            'harga_sewa' => ['sometimes', 'required', 'numeric', 'min:0'],
            'status' => ['sometimes', 'required', 'string', 'in:tersedia,disewa,perawatan'],
        ];
    }
}
