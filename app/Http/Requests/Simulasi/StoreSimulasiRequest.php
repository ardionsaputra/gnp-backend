<?php

namespace App\Http\Requests\Simulasi;

use Illuminate\Foundation\Http\FormRequest;

class StoreSimulasiRequest extends FormRequest
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
            'jenis_kebutuhan' => ['required', 'string', 'max:100'],
            'kapasitas_beban' => ['required', 'numeric', 'gt:0'],
            'lokasi_proyek' => ['required', 'string', 'max:255'],
            'periode_penggunaan' => ['required', 'string', 'max:100'],
            'jenis_unit' => ['nullable', 'string', 'max:50'],
            'id_pelanggan' => ['nullable', 'integer', 'exists:pelanggan,id_pelanggan'],
        ];
    }
}
