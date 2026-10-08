<?php

namespace App\Http\Requests\Porezi;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UvozRucniUveziRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'sacuvaj_sablon' => ['nullable', 'boolean'],
            'naziv_sablona' => ['nullable', 'required_if:sacuvaj_sablon,1', 'string', 'max:100'],
        ];
    }
}
