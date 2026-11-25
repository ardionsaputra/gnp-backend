<?php

namespace App\Http\Requests\Rekanan;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRekananRequest extends FormRequest
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
            'nama_rekanan' => ['sometimes', 'required', 'string', 'max:150'],
            'kontak' => ['sometimes', 'required', 'string', 'max:150'],
        ];
    }
}
