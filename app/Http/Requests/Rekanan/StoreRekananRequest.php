<?php

namespace App\Http\Requests\Rekanan;

use Illuminate\Foundation\Http\FormRequest;

class StoreRekananRequest extends FormRequest
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
            'nama_rekanan' => ['required', 'string', 'max:150'],
            'kontak' => ['required', 'string', 'max:150'],
        ];
    }
}
