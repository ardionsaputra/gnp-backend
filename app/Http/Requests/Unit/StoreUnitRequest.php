<?php

namespace App\Http\Requests\Unit;

use Illuminate\Foundation\Http\FormRequest;

class StoreUnitRequest extends FormRequest
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
            'nama_unit' => ['required', 'string', 'max:150'],
            'jenis_unit' => ['required', 'string', 'max:50'],
            'kapasitas' => ['required', 'numeric', 'gt:0'],
            'spesifikasi' => ['required', 'string', 'max:1000'],
            'lokasi_unit' => ['required', 'string', 'max:150'],
            'sumber_unit' => ['required', 'string', 'in:internal,rekanan'],
            'harga_sewa' => ['required', 'numeric', 'min:0'],
            'status' => ['sometimes', 'string', 'in:tersedia,disewa,perawatan'],
        ];
    }
}
